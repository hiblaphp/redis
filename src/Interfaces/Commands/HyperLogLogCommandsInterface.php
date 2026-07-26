<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;

interface HyperLogLogCommandsInterface
{
    /**
     * Adds the specified elements to the specified HyperLogLog.
     *
     * @param string $key The key holding the HyperLogLog.
     * @param string ...$elements One or more elements to add.
     *
     * @return PromiseInterface<int> Resolves to 1 if the internal register was altered, 0 otherwise.
     */
    public function pfadd(string $key, string ...$elements): PromiseInterface;

    /**
     * Returns the approximated cardinality of the specified HyperLogLog(s).
     *
     * @param string|array<int, string> $keys One or more keys to count.
     * @param string ...$moreKeys Additional keys.
     *
     * @return PromiseInterface<int> The approximated number of unique elements.
     */
    public function pfcount(string|array $keys, string ...$moreKeys): PromiseInterface;

    /**
     * Merge multiple HyperLogLog values into an unique value.
     *
     * @param string $destKey The destination key.
     * @param string|array<int, string> $sourceKeys One or more source keys to merge.
     * @param string ...$moreSourceKeys Additional source keys.
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function pfmerge(string $destKey, string|array $sourceKeys, string ...$moreSourceKeys): PromiseInterface;
}
