<?php

declare(strict_types=1);

namespace Hibla\Redis\Internals;

use Hibla\Promise\Interfaces\PromiseInterface;

/**
 * @internal Tracks in-flight state for a single cluster transaction() call so it can be
 * shared between the fiber body and its onCancel handler without loose references.
 */
final class ClusterTransactionExecutionState
{
    public bool $isCancelled = false;

    public ?RedisClusterTransaction $activeTx = null;

    /**
     * @var PromiseInterface<mixed>|null
     */
    public ?PromiseInterface $innerWorkPromise = null;
}
