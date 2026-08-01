<?php

declare(strict_types=1);

namespace Hibla\Redis\Cluster;

use Hibla\Redis\Internals\ScanStream;

/**
 * @template TKey
 * @template TValue
 *
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class ClusterScanStream implements \IteratorAggregate
{
    /**
     * @param array<int, ScanStream<TKey, TValue>> $streams
     */
    public function __construct(private readonly array $streams)
    {
    }

    public function getIterator(): \Generator
    {
        foreach ($this->streams as $stream) {
            foreach ($stream as $key => $value) {
                yield $key => $value;
            }
        }
    }

    public function cancel(): void
    {
        foreach ($this->streams as $stream) {
            $stream->cancel();
        }
    }
}
