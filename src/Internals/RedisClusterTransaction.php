<?php

declare(strict_types=1);

namespace Hibla\Redis\Internals;

use Hibla\EventLoop\Loop;
use Hibla\Promise\Exceptions\CancelledException;
use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\Cluster\ClusterTopology;
use Hibla\Redis\Cluster\KeyExtractor;
use Hibla\Redis\Cluster\SlotCalculator;
use Hibla\Redis\Exceptions\RedisException;
use Hibla\Redis\Exceptions\TransactionException;
use Hibla\Redis\Interfaces\CommandInterface;
use Hibla\Redis\Interfaces\NodeClientInterface;
use Hibla\Redis\Interfaces\RedisTransactionInterface;
use Hibla\Redis\Traits\Commands\RedisCommandsTrait;

use function Hibla\async;
use function Hibla\await;

/**
 * @internal Created by RedisCluster::transaction(). Do not instantiate directly.
 */
final class RedisClusterTransaction implements RedisTransactionInterface
{
    use RedisCommandsTrait;

    private ?int $lockedSlot = null;

    private bool $isMulti = false;

    /**
     * @var PromiseInterface<RedisTransactionInterface>|null
     */
    private ?PromiseInterface $internalTxPromise = null;

    /**
     * @var Promise<void>|null
     */
    private ?Promise $holdPromise = null;

    private ?RedisTransactionInterface $internalTx = null;

    /**
     * @param \Closure(string): NodeClientInterface $clientFactory
     */
    public function __construct(
        private readonly ClusterTopology $topology,
        private readonly \Closure $clientFactory
    ) {}

    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    public function executeCommand(CommandInterface $command): PromiseInterface
    {
        $key = KeyExtractor::extract($command);

        /** @var PromiseInterface<TReturn> $promise */
        $promise = $this->getInternalTx($key)->then(
            function (RedisTransactionInterface $tx) use ($command) {
                return $tx->executeCommand($command);
            }
        );

        return Promise::propagateCancellation($promise);
    }

    /**
     * @return PromiseInterface<string>
     */
    public function watch(string ...$keys): PromiseInterface
    {
        if ($keys === []) {
            /** @var PromiseInterface<string> $resolved */
            $resolved = Promise::resolved('OK');

            return $resolved;
        }

        foreach ($keys as $key) {
            $cmdSlot = SlotCalculator::calculate($key);

            if ($this->lockedSlot === null) {
                $this->lockedSlot = $cmdSlot;
            } elseif ($this->lockedSlot !== $cmdSlot) {
                return Promise::rejected(new TransactionException('Cross-slot transaction attempted in WATCH.'));
            }
        }

        $firstKey = $keys[0];

        /** @var PromiseInterface<string> $promise */
        $promise = $this->getInternalTx($firstKey)->then(
            function (RedisTransactionInterface $tx) use ($keys) {
                return $tx->watch(...$keys);
            }
        );

        return Promise::propagateCancellation($promise);
    }

    /**
     * @return PromiseInterface<string>
     */
    public function unwatch(): PromiseInterface
    {
        if ($this->internalTxPromise === null) {
            /** @var PromiseInterface<string> $resolved */
            $resolved = Promise::resolved('OK');

            return $resolved;
        }

        /** @var PromiseInterface<string> $promise */
        $promise = $this->internalTxPromise->then(
            function (RedisTransactionInterface $tx) {
                return $tx->unwatch();
            }
        );

        return Promise::propagateCancellation($promise);
    }

    /**
     * @return PromiseInterface<string>
     */
    public function multi(): PromiseInterface
    {
        $this->isMulti = true;

        if ($this->internalTxPromise === null) {
            /** @var PromiseInterface<string> $resolved */
            $resolved = Promise::resolved('OK');

            return $resolved;
        }

        /** @var PromiseInterface<string> $promise */
        $promise = $this->internalTxPromise->then(
            function (RedisTransactionInterface $tx) {
                return $tx->multi();
            }
        );

        return Promise::propagateCancellation($promise);
    }

    /**
     * @return PromiseInterface<array<int, mixed>|null>
     */
    public function exec(): PromiseInterface
    {
        if ($this->internalTxPromise === null) {
            /** @var PromiseInterface<array<int, mixed>|null> $resolved */
            $resolved = Promise::resolved([]);

            return $resolved;
        }

        /** @var PromiseInterface<array<int, mixed>|null> $promise */
        $promise = $this->internalTxPromise->then(
            function (RedisTransactionInterface $tx) {
                return $tx->exec();
            }
        );

        // Defer connection cleanup to next tick so the result cleanly returns
        $promise->finally(function () {
            Loop::microTask(function () {
                $this->releaseInternal();
            });
        })->catch(fn() => null);

        return Promise::propagateCancellation($promise);
    }

      /**
     * @return PromiseInterface<string>
     */
    public function discard(): PromiseInterface
    {
        if ($this->internalTxPromise === null) {
            /** @var PromiseInterface<string> $resolved */
            $resolved = Promise::resolved('OK');

            return $resolved;
        }

        /** @var PromiseInterface<string> $promise */
        $promise = $this->internalTxPromise->then(
            function (RedisTransactionInterface $tx) {
                return $tx->discard();
            }
        );

        $promise->finally(function () {
            Loop::microTask(function () {
                $this->releaseInternal();
            });
        })->catch(fn () => null);

        return Promise::propagateCancellation($promise);
    }

    public function isActive(): bool
    {
        return $this->internalTx !== null && method_exists($this->internalTx, 'isActive') ? $this->internalTx->isActive() : true;
    }

    public function isInMulti(): bool
    {
        return $this->internalTx !== null && method_exists($this->internalTx, 'isInMulti') ? $this->internalTx->isInMulti() : $this->isMulti;
    }

    public function forceCancelCurrentQuery(): void
    {
        if ($this->internalTx !== null && method_exists($this->internalTx, 'forceCancelCurrentQuery')) {
            $this->internalTx->forceCancelCurrentQuery();
        }
    }

    public function abort(): PromiseInterface
    {
        return async(function () {
            if ($this->internalTx !== null && method_exists($this->internalTx, 'abort')) {
                await($this->internalTx->abort());
            }

            // Reject the hold promise, triggering the underlying RedisClient's robust cancellation logic!
            $this->releaseInternal(new CancelledException('Transaction aborted'));

            return null;
        });
    }

    public function release(): void
    {
        $this->releaseInternal();
    }

    /**
     * Lazy-loads the actual node transaction once we know which slot we are targeting.
     */
    private function getInternalTx(?string $key = null): PromiseInterface
    {
        if ($key !== null) {
            $cmdSlot = SlotCalculator::calculate($key);

            if ($this->lockedSlot === null) {
                $this->lockedSlot = $cmdSlot;
            } elseif ($this->lockedSlot !== $cmdSlot) {
                return Promise::rejected(new TransactionException('Cross-slot transaction attempted. All keys in a cluster transaction must map to the same hash slot using Hash Tags {}.'));
            }
        }

        if ($this->internalTxPromise !== null) {
            return $this->internalTxPromise;
        }

        /** @var Promise<RedisTransactionInterface> $txPromise */
        $txPromise = new Promise();
        $this->internalTxPromise = $txPromise;

        /** @var Promise<void> $holdPromise */
        $holdPromise = new Promise();
        $this->holdPromise = $holdPromise;

        $nodeUri = $this->topology->getNodeForSlot($this->lockedSlot);
        $nodeClient = ($this->clientFactory)($nodeUri);

        if (! method_exists($nodeClient, 'transaction')) {
            $txPromise->reject(new RedisException('Underlying node client does not support transactions.'));

            return $txPromise;
        }

        $nodeClient->transaction(function (RedisTransactionInterface $tx) use ($txPromise, $holdPromise) {
            $this->internalTx = $tx;

            if ($this->isMulti) {
                $tx->multi()->then(
                    fn() => $txPromise->resolve($tx),
                    fn(\Throwable $e) => $txPromise->reject($e)
                );
            } else {
                $txPromise->resolve($tx);
            }

            return await($holdPromise);
        })->catch(function (\Throwable $e) use ($txPromise) {
            if ($txPromise->isPending()) {
                $txPromise->reject($e);
            }
        });

        return $txPromise;
    }

    private function releaseInternal(?\Throwable $error = null): void
    {
        if ($this->holdPromise !== null && $this->holdPromise->isPending()) {
            if ($error !== null) {
                $this->holdPromise->reject($error);
            } else {
                $this->holdPromise->resolve(null);
            }
        }
        $this->holdPromise = null;
        $this->internalTxPromise = null;
        $this->internalTx = null;
    }

    public function __destruct()
    {
        $this->releaseInternal();
    }
}
