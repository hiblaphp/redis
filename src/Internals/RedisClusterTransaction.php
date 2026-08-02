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
use Hibla\Redis\Interfaces\RedisClientInterface;
use Hibla\Redis\Interfaces\RedisTransactionInterface;
use Hibla\Redis\Traits\Commands\RedisCommandsTrait;

use function Hibla\await;

/**
 * @internal Created by RedisCluster::transaction(). Do not instantiate directly.
 */
final class RedisClusterTransaction implements InternalTransactionInterface
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

    private ?InternalTransactionInterface $internalTx = null;

    /**
     * @param \Closure(string): NodeClientInterface $clientFactory
     */
    public function __construct(
        private readonly ClusterTopology $topology,
        private readonly \Closure $clientFactory
    ) {
    }

    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * {@inheritDoc}
     *
     * @return PromiseInterface<TReturn>
     */
    public function executeCommand(CommandInterface $command): PromiseInterface
    {
        $key = KeyExtractor::extract($command);

        $promise = $this->getInternalTx($key)->then(
            fn (RedisTransactionInterface $tx) => $tx->executeCommand($command)
        );

        return Promise::propagateCancellation($promise);
    }

    /**
     * {@inheritDoc}
     */
    public function watch(string ...$keys): PromiseInterface
    {
        if ($keys === []) {
            return Promise::resolved('OK');
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
        $promise = $this->getInternalTx($firstKey)->then(fn (RedisTransactionInterface $tx) => $tx->watch(...$keys));

        return Promise::propagateCancellation($promise);
    }

    /**
     * {@inheritDoc}
     */
    public function unwatch(): PromiseInterface
    {
        if ($this->internalTxPromise === null) {
            return Promise::resolved('OK');
        }

        $promise = $this->internalTxPromise->then(fn (RedisTransactionInterface $tx) => $tx->unwatch());

        return Promise::propagateCancellation($promise);
    }

    /**
     * {@inheritDoc}
     */
    public function multi(): PromiseInterface
    {
        $this->isMulti = true;

        if ($this->internalTxPromise === null) {
            return Promise::resolved('OK');
        }

        $promise = $this->internalTxPromise->then(fn (RedisTransactionInterface $tx) => $tx->multi());

        return Promise::propagateCancellation($promise);
    }

    /**
     * {@inheritDoc}
     */
    public function exec(): PromiseInterface
    {
        $this->isMulti = false;

        if ($this->internalTxPromise === null) {
            return Promise::resolved([]);
        }

        $promise = $this->internalTxPromise->then(fn (RedisTransactionInterface $tx) => $tx->exec());

        $promise
            ->finally(function () {
                Loop::nextTick($this->releaseInternal(...));
            })
            ->catch(fn () => null)
        ;

        return Promise::propagateCancellation($promise);
    }

    /**
     * {@inheritDoc}
     */
    public function discard(): PromiseInterface
    {
        $this->isMulti = false;

        if ($this->internalTxPromise === null) {
            return Promise::resolved('OK');
        }

        $promise = $this->internalTxPromise->then(fn (RedisTransactionInterface $tx) => $tx->discard());

        $promise
            ->finally(function () {
                Loop::nextTick($this->releaseInternal(...));
            })
            ->catch(fn () => null)
        ;

        return Promise::propagateCancellation($promise);
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        return $this->internalTx !== null ? $this->internalTx->isActive() : true;
    }

    /**
     * {@inheritDoc}
     */
    public function isInMulti(): bool
    {
        return $this->internalTx !== null ? $this->internalTx->isInMulti() : $this->isMulti;
    }

    /**
     * {@inheritDoc}
     */
    public function forceCancelCurrentQuery(): void
    {
        $this->internalTx?->forceCancelCurrentQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function abort(): PromiseInterface
    {
        if ($this->internalTx !== null) {
            return $this->internalTx->abort()
                ->then(function (): void {
                    $this->releaseInternal(new CancelledException('Transaction aborted'));
                })
            ;
        }

        $this->releaseInternal(new CancelledException('Transaction aborted'));

        return Promise::resolved();
    }

    public function release(): void
    {
        $this->releaseInternal();
    }

    /**
     * Lazy-loads the actual node transaction the cluster knows about.
     *
     * @return PromiseInterface<RedisTransactionInterface>
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

        if (! $nodeClient instanceof RedisClientInterface) {
            $txPromise->reject(new RedisException('Underlying node client does not support transactions (must implement RedisClientInterface).'));

            return $txPromise;
        }

        $nodeClient->transaction(function (RedisTransactionInterface $tx) use ($txPromise, $holdPromise) {
            if (! $tx instanceof InternalTransactionInterface) {
                $txPromise->reject(new RedisException('Underlying transaction instance does not support internal lifecycle methods.'));

                await($holdPromise);

                return null;
            }

            $this->internalTx = $tx;

            if ($this->isMulti) {
                $tx->multi()->then(
                    fn () => $txPromise->resolve($tx),
                    $txPromise->reject(...)
                );
            } else {
                $txPromise->resolve($tx);
            }

            await($holdPromise);

            return null;
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
