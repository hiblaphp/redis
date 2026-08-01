<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces;

/**
 * Common contract for asynchronous, cancellable key-value streams
 * returned by scanStream() implementations (single-node or cluster-wide).
 *
 * @template TKey
 * @template TValue
 *
 * @extends \IteratorAggregate<TKey, TValue>
 */
interface ScanStreamInterface extends \IteratorAggregate
{
    /**
     * Cancels the stream and releases any in-flight resources.
     */
    public function cancel(): void;
}
