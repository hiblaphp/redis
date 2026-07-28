<?php

declare(strict_types=1);

namespace Hibla\Redis;

use Hibla\EventLoop\Loop;
use Hibla\Promise\Exceptions\CancelledException;
use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\Command\Transactions\ExecCommand;
use Hibla\Redis\Command\Transactions\MultiCommand;
use Hibla\Redis\Exceptions\ConnectionException;
use Hibla\Redis\Interfaces\CommandInterface;
use Hibla\Redis\Interfaces\RedisClientInterface;
use Hibla\Redis\Interfaces\RedisTransactionInterface;
use Hibla\Redis\Internals\CommandValidator;
use Hibla\Redis\Internals\Connection;
use Hibla\Redis\Internals\ExecutionState;
use Hibla\Redis\Internals\Pipeline;
use Hibla\Redis\Internals\RedisSubscriber;
use Hibla\Redis\Internals\RedisTransaction;
use Hibla\Redis\Internals\TransactionExecutionState;
use Hibla\Redis\Manager\PoolManager;
use Hibla\Redis\Traits\Commands\RedisCommandsTrait;
use Hibla\Redis\ValueObjects\RedisConfig;
use Hibla\Redis\ValueObjects\RetryConfig;
use Hibla\Socket\Interfaces\ConnectorInterface;

use function Hibla\async;
use function Hibla\await;

final class RedisClient implements RedisClientInterface
{
    use RedisCommandsTrait;

    private ?PoolManager $pool = null;

    private bool $isClosing = false;

    /**
     * @var PromiseInterface<void>|null
     */
    private ?PromiseInterface $closePromise = null;

    /**
     * @var RedisConfig|array<string, mixed>|string
     */
    private RedisConfig|array|string $config;

    public readonly RetryConfig $retryConfig;

    /**
     * @param RedisConfig|array<string, mixed>|string $config
     */
    public function __construct(
        RedisConfig|array|string $config,
        int $minConnections = 0,
        int $maxConnections = 10,
        int $idleTimeout = 60,
        int $maxLifetime = 3600,
        int $maxWaiters = 0,
        float $acquireTimeout = 10.0,
        ?RetryConfig $retryConfig = null,
        ?ConnectorInterface $connector = null,
    ) {
        $this->config = $config;
        $this->retryConfig = $retryConfig ?? new RetryConfig();

        $this->pool = new PoolManager(
            config: $config,
            maxSize: $maxConnections,
            minSize: $minConnections,
            idleTimeout: $idleTimeout,
            maxLifetime: $maxLifetime,
            maxWaiters: $maxWaiters,
            acquireTimeout: $acquireTimeout,
            connector: $connector,
            retryConfig: $this->retryConfig
        );
    }

    /**
     * {@inheritDoc}
     */
    public array $stats {
        get {
            if ($this->pool === null) {
                return [];
            }

            return $this->pool->stats;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    public function executeCommand(CommandInterface $command): PromiseInterface
    {
        if ($this->pool === null) {
            return Promise::rejected(new ConnectionException('Client is closed'));
        }

        $pool = $this->pool;

        if (($error = CommandValidator::checkValidForPool($command)) !== null) {
            return Promise::rejected($error);
        }

        /** @var Promise<TReturn> $outerPromise */
        $outerPromise = new Promise();
        $state = new ExecutionState();

        $attemptExecution = function () use (
            $command,
            $pool,
            $state,
            $outerPromise,
            &$attemptExecution,
        ): void {
            if ($outerPromise->isCancelled()) {
                return;
            }

            // STAGE 1: Acquire Connection from Pool
            $state->activePromise = $pool->get();

            $state->activePromise->then(
                function (Connection $conn) use (
                    $command,
                    $pool,
                    $state,
                    $outerPromise,
                    &$attemptExecution,
                ): void {
                    if ($outerPromise->isCancelled()) {
                        $pool->release($conn);

                        return;
                    }

                    $state->activePromise = $conn->enqueue($command);

                    $state->activePromise->then(
                        function (mixed $result) use ($pool, $conn, $outerPromise): void {
                            $pool->release($conn);

                            if (! $outerPromise->isSettled()) {
                                $outerPromise->resolve($result);
                            }
                        },
                        function (\Throwable $e) use (
                            $pool,
                            $conn,
                            $state,
                            $outerPromise,
                            &$attemptExecution
                        ): void {
                            if ($e instanceof ConnectionException) {
                                $pool->removeConnection($conn);
                            } else {
                                $pool->release($conn);
                            }

                            if ($outerPromise->isCancelled()) {
                                return;
                            }

                            if ($e instanceof ConnectionException && $state->attempt < $this->retryConfig->maxRetries) {
                                $state->attempt++;
                                $delay = $this->retryConfig->getDelay($state->attempt);

                                $state->timerId = Loop::addTimer($delay, static function () use ($state, &$attemptExecution): void {
                                    $state->timerId = null;
                                    $attemptExecution();
                                });
                            } else {
                                if (! $outerPromise->isSettled()) {
                                    $outerPromise->reject($e);
                                }
                            }
                        }
                    );
                },
                function (\Throwable $e) use (
                    $state,
                    $outerPromise,
                    &$attemptExecution,
                ): void {
                    if ($outerPromise->isCancelled()) {
                        return;
                    }

                    if ($e instanceof ConnectionException && $state->attempt < $this->retryConfig->maxRetries) {
                        $state->attempt++;
                        $delay = $this->retryConfig->getDelay($state->attempt);

                        $state->timerId = Loop::addTimer($delay, static function () use ($state, &$attemptExecution): void {
                            $state->timerId = null;
                            $attemptExecution();
                        });
                    } else {
                        if (! $outerPromise->isSettled()) {
                            $outerPromise->reject($e);
                        }
                    }
                }
            );
        };

        $outerPromise->onCancel(function () use ($state): void {
            if ($state->timerId !== null) {
                Loop::cancelTimer($state->timerId);
                $state->timerId = null;
            }

            if ($state->activePromise !== null && ! $state->activePromise->isSettled()) {
                $state->activePromise->cancel();
                $state->activePromise = null;
            }
        });

        $attemptExecution();

        return Promise::propagateCancellation($outerPromise);
    }

    /**
     * {@inheritDoc}
     */
    public function pipeline(callable $callback): PromiseInterface
    {
        if ($this->pool === null) {
            return Promise::rejected(new ConnectionException('Client is closed'));
        }

        $pool = $this->pool;

        $pipeline = new Pipeline();
        $callback($pipeline);

        $pipeline->lock();
        $commands = $pipeline->commands;

        if ($commands === []) {
            return Promise::resolved([]);
        }

        if (($error = CommandValidator::checkBatchValidForPool($commands)) !== null) {
            return Promise::rejected($error);
        }

        /** @var Promise<array<int, mixed>> $outerPromise */
        $outerPromise = new Promise();
        $state = new ExecutionState();

        $attemptExecution = function () use (
            $commands,
            $pool,
            $state,
            $outerPromise,
            &$attemptExecution,
        ): void {
            if ($outerPromise->isCancelled()) {
                return;
            }

            $state->activePromise = $pool->get();

            $state->activePromise->then(
                function (Connection $conn) use (
                    $commands,
                    $pool,
                    $state,
                    $outerPromise,
                    &$attemptExecution,
                ): void {
                    if ($outerPromise->isCancelled()) {
                        $pool->release($conn);

                        return;
                    }

                    $state->activePromise = $conn->enqueueBatch($commands);

                    $state->activePromise->then(
                        function (array $results) use ($pool, $conn, $outerPromise): void {
                            $pool->release($conn);

                            if (! $outerPromise->isSettled()) {
                                $outerPromise->resolve($results);
                            }
                        },
                        function (\Throwable $e) use (
                            $pool,
                            $conn,
                            $state,
                            $outerPromise,
                            &$attemptExecution,
                        ): void {
                            if ($e instanceof ConnectionException) {
                                $pool->removeConnection($conn);
                            } else {
                                $pool->release($conn);
                            }

                            if ($outerPromise->isCancelled()) {
                                return;
                            }

                            if ($e instanceof ConnectionException && $state->attempt < $this->retryConfig->maxRetries) {
                                $state->attempt++;
                                $delay = $this->retryConfig->getDelay($state->attempt);

                                $state->timerId = Loop::addTimer($delay, static function () use ($state, &$attemptExecution): void {
                                    $state->timerId = null;
                                    $attemptExecution();
                                });
                            } else {
                                if (! $outerPromise->isSettled()) {
                                    $outerPromise->reject($e);
                                }
                            }
                        }
                    );
                },
                function (\Throwable $e) use (
                    $state,
                    $outerPromise,
                    &$attemptExecution,
                ): void {
                    if ($outerPromise->isCancelled()) {
                        return;
                    }

                    if ($e instanceof ConnectionException && $state->attempt < $this->retryConfig->maxRetries) {
                        $state->attempt++;
                        $delay = $this->retryConfig->getDelay($state->attempt);

                        $state->timerId = Loop::addTimer($delay, static function () use ($state, &$attemptExecution): void {
                            $state->timerId = null;
                            $attemptExecution();
                        });
                    } else {
                        if (! $outerPromise->isSettled()) {
                            $outerPromise->reject($e);
                        }
                    }
                }
            );
        };

        $outerPromise->onCancel(function () use ($state): void {
            if ($state->timerId !== null) {
                Loop::cancelTimer($state->timerId);
                $state->timerId = null;
            }
            if ($state->activePromise !== null && ! $state->activePromise->isSettled()) {
                $state->activePromise->cancel();
                $state->activePromise = null;
            }
        });

        $attemptExecution();

        return Promise::propagateCancellation($outerPromise);
    }

    /**
     * @inheritDoc
     */
    public function atomic(callable $callback): PromiseInterface
    {
        if ($this->pool === null) {
            return Promise::rejected(new ConnectionException('Client is closed'));
        }

        $pool = $this->pool;

        $pipeline = new Pipeline();
        $callback($pipeline);

        $pipeline->lock();
        $commands = $pipeline->commands;

        if ($commands === []) {
            return Promise::resolved([]);
        }

        if (($error = CommandValidator::checkBatchValidForPool($commands)) !== null) {
            return Promise::rejected($error);
        }

        $wrappedCommands = [new MultiCommand(), ...$commands, new ExecCommand()];

        /** @var Promise<array<int, mixed>> $outerPromise */
        $outerPromise = new Promise();
        $state = new ExecutionState();

        $attemptExecution = function () use (
            $commands,
            $wrappedCommands,
            $pool,
            $state,
            $outerPromise,
            &$attemptExecution,
        ): void {
            if ($outerPromise->isCancelled()) {
                return;
            }

            $state->activePromise = $pool->get();

            $state->activePromise->then(
                function (Connection $conn) use (
                    $commands,
                    $wrappedCommands,
                    $pool,
                    $state,
                    $outerPromise,
                    &$attemptExecution,
                ): void {
                    if ($outerPromise->isCancelled()) {
                        $pool->release($conn);

                        return;
                    }

                    $state->activePromise = $conn->enqueueBatch($wrappedCommands);

                    $state->activePromise->then(
                        function (array $results) use ($pool, $conn, $commands, $outerPromise): void {
                            $pool->release($conn);

                            if ($outerPromise->isCancelled()) {
                                return;
                            }

                            $execResults = array_pop($results);

                            if ($execResults instanceof \Throwable) {
                                if (! $outerPromise->isSettled()) {
                                    $outerPromise->reject($execResults);
                                }

                                return;
                            }

                            if (! \is_array($execResults)) {
                                if (! $outerPromise->isSettled()) {
                                    $outerPromise->resolve($execResults ?? []);
                                }

                                return;
                            }

                            $formatted = [];
                            foreach ($execResults as $i => $raw) {
                                if ($raw instanceof \Throwable) {
                                    $formatted[$i] = $raw;
                                } elseif (isset($commands[$i])) {
                                    $formatted[$i] = $commands[$i]->parseResponse($raw);
                                } else {
                                    $formatted[$i] = $raw;
                                }
                            }

                            if (! $outerPromise->isSettled()) {
                                $outerPromise->resolve($formatted);
                            }
                        },
                        function (\Throwable $e) use (
                            $pool,
                            $conn,
                            $state,
                            $outerPromise,
                            &$attemptExecution,
                        ): void {
                            if ($e instanceof ConnectionException) {
                                $pool->removeConnection($conn);
                            } else {
                                $pool->release($conn);
                            }

                            if ($outerPromise->isCancelled()) {
                                return;
                            }

                            if ($e instanceof ConnectionException && $state->attempt < $this->retryConfig->maxRetries) {
                                $state->attempt++;
                                $delay = $this->retryConfig->getDelay($state->attempt);

                                $state->timerId = Loop::addTimer($delay, static function () use ($state, &$attemptExecution): void {
                                    $state->timerId = null;
                                    $attemptExecution();
                                });
                            } else {
                                if (! $outerPromise->isSettled()) {
                                    $outerPromise->reject($e);
                                }
                            }
                        }
                    );
                },
                function (\Throwable $e) use (
                    $state,
                    $outerPromise,
                    &$attemptExecution,
                ): void {
                    if ($outerPromise->isCancelled()) {
                        return;
                    }

                    if ($e instanceof ConnectionException && $state->attempt < $this->retryConfig->maxRetries) {
                        $state->attempt++;
                        $delay = $this->retryConfig->getDelay($state->attempt);

                        $state->timerId = Loop::addTimer($delay, static function () use ($state, &$attemptExecution): void {
                            $state->timerId = null;
                            $attemptExecution();
                        });
                    } else {
                        if (! $outerPromise->isSettled()) {
                            $outerPromise->reject($e);
                        }
                    }
                }
            );
        };

        $outerPromise->onCancel(function () use ($state): void {
            if ($state->timerId !== null) {
                Loop::cancelTimer($state->timerId);
                $state->timerId = null;
            }
            if ($state->activePromise !== null && ! $state->activePromise->isSettled()) {
                $state->activePromise->cancel();
                $state->activePromise = null;
            }
        });

        $attemptExecution();

        return Promise::propagateCancellation($outerPromise);
    }

    /**
     * {@inheritDoc}
     */
    public function createSubscriber(?RetryConfig $retryConfig = null): PromiseInterface
    {
        $subscriber = new RedisSubscriber(
            $this->config,
            $retryConfig ?? $this->retryConfig
        );

        $promise = $subscriber->initialize()->then(function () use ($subscriber) {
            return $subscriber;
        });

        $promise->onCancel($subscriber->close(...));

        return Promise::propagateCancellation($promise);
    }

    /**
     * {@inheritDoc}
     *
     * @template TResult
     *
     * @param callable(RedisTransactionInterface): TResult $callback
     *
     * @return PromiseInterface<TResult>
     */
    public function transaction(callable $callback): PromiseInterface
    {
        if ($this->pool === null) {
            return Promise::rejected(new ConnectionException('Client is closed'));
        }

        $pool = $this->pool;
        $state = new TransactionExecutionState();

        $fiberPromise = async(function () use ($callback, $pool, $state) {
            if ($state->isCancelled) {
                return null;
            }

            try {
                $state->poolPromise = $pool->get();
                /** @var Connection $conn */
                $conn = await($state->poolPromise);
                $state->poolPromise = null;

                // @phpstan-ignore-next-line if.alwaysFalse
                if ($state->isCancelled) {
                    $pool->release($conn);

                    return null;
                }

                $state->activeTx = new RedisTransaction($conn, $pool);

                $state->innerWorkPromise = async(fn () => $callback($state->activeTx));
                $result = await($state->innerWorkPromise);

                if ($state->activeTx->isInMulti()) {
                    $execResult = await($state->activeTx->exec());

                    /** @var TResult $execResult */
                    return $execResult; // @phpstan-ignore varTag.type
                }

                return $result;
            } catch (\Throwable $e) {
                // @phpstan-ignore-next-line if.alwaysFalse
                if ($state->isCancelled) {
                    return null;
                }

                if (
                    $e instanceof CancelledException
                    && $state->innerWorkPromise !== null
                    && ! $state->innerWorkPromise->isSettled()
                ) {
                    $state->innerWorkPromise->cancel();
                }

                if ($state->activeTx !== null && $state->activeTx->isActive()) {
                    try {
                        $state->activeTx->forceCancelCurrentQuery();
                        await($state->activeTx->abort());
                    } catch (\Throwable) {
                        // Ignore cleanup failure and the original exception takes precedence
                    }
                }

                throw $e;
            } finally {
                if ($state->activeTx !== null) {
                    $state->activeTx->release();
                    $state->activeTx = null;
                }
            }
        });

        $fiberPromise->onCancel(function () use ($state): void {
            $state->isCancelled = true;

            if ($state->poolPromise !== null && ! $state->poolPromise->isSettled()) {
                $state->poolPromise->cancel();
            }

            if ($state->innerWorkPromise !== null && ! $state->innerWorkPromise->isSettled()) {
                $state->innerWorkPromise->cancel();
            }

            if ($state->activeTx !== null && $state->activeTx->isActive()) {
                $state->activeTx->forceCancelCurrentQuery();
            }
        });

        /** @var PromiseInterface<TResult> $resultPromise */
        $resultPromise = Promise::propagateCancellation($fiberPromise);

        return $resultPromise;
    }

    /**
     * {@inheritDoc}
     */
    public function healthCheck(): PromiseInterface
    {
        if ($this->pool === null) {
            return Promise::rejected(new ConnectionException('Client is closed'));
        }

        return $this->pool->healthCheck();
    }

    /**
     * {@inheritDoc}
     */
    public function closeAsync(float $timeout = 0.0): PromiseInterface
    {
        if ($this->pool === null) {
            return Promise::resolved();
        }

        if ($this->closePromise !== null) {
            return $this->closePromise;
        }

        $pool = $this->pool;

        $this->closePromise = $pool->closeAsync($timeout)
            ->then(function (): void {
                if ($this->isClosing) {
                    return;
                }

                $this->pool = null;
                $this->closePromise = null;
            })
        ;

        return $this->closePromise;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if ($this->pool === null) {
            return;
        }

        $this->isClosing = true;

        $this->pool->close();
        $this->pool = null;
        $this->closePromise = null;

        $this->isClosing = false;
    }

    public function __destruct()
    {
        $this->close();
    }
}
