<?php

declare(strict_types=1);

namespace Hibla\Redis\Internals;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Interfaces\RedisTransactionInterface;

/**
 * @internal Contract for transaction implementations to expose lifecycle methods
 * required by the cancellation and execution engines.
 */
interface InternalTransactionInterface extends RedisTransactionInterface
{

    /**
     * Checks if the transaction is still active and valid.
     */
    public function isActive(): bool;

    /**
     * Returns true if the transaction is currently in the MULTI queuing state.
     */
    public function isInMulti(): bool;

    /**
     * Force-cancels any running query on the connection and clears the queue.
     * Called automatically before discard() or cleanup to clear the wire.
     */
    public function forceCancelCurrentQuery(): void;

    /**
     * Forces the transaction to abort and clean up the connection.
     *
     * @return PromiseInterface<void> Resolves when the transaction has been aborted and the connection is cleaned up.
     */
    public function abort(): PromiseInterface;
}
