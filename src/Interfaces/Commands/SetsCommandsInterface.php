<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Interfaces\ScanStreamInterface;

interface SetsCommandsInterface
{
    /**
     * Adds members to set stored at key.
     *
     * @param string $key Set key.
     * @param mixed ...$members Members to add.
     *
     * @return PromiseInterface<int> Number of elements added.
     */
    public function sadd(string $key, mixed ...$members): PromiseInterface;

    /**
     * Removes members from set stored at key.
     *
     * @param string $key Set key.
     * @param mixed ...$members Members to remove.
     *
     * @return PromiseInterface<int> Number of elements removed.
     */
    public function srem(string $key, mixed ...$members): PromiseInterface;

    /**
     * Returns all members of set stored at key.
     *
     * @param string $key Set key.
     *
     * @return PromiseInterface<array<int, string>> Array of all set members.
     */
    public function smembers(string $key): PromiseInterface;

    /**
     * Returns if member belongs to set stored at key.
     *
     * @param string $key Set key.
     * @param mixed $member Member to test.
     *
     * @return PromiseInterface<int> 1 if member, 0 otherwise.
     */
    public function sismember(string $key, mixed $member): PromiseInterface;

    /**
     * Returns the set cardinality (number of elements) of the set stored at key.
     *
     * @param string $key Set key.
     *
     * @return PromiseInterface<int> Number of elements in the set, or 0 if key does not exist.
     */
    public function scard(string $key): PromiseInterface;

    /**
     * Removes and returns one or more random members from the set value stored at key.
     *
     * @param string $key Set key.
     * @param int|null $count Number of members to return and remove.
     *
     * @return PromiseInterface<string|array<int, string>|null> A single string if count is null, an array if count is provided, or null if key does not exist.
     */
    public function spop(string $key, ?int $count = null): PromiseInterface;

    /**
     * Returns the members of the set resulting from the intersection of all the given sets.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to intersect.
     *
     * @return PromiseInterface<array<int, string>> Array of members in the resulting intersected set.
     */
    public function sinter(string|array $keys, string ...$moreKeys): PromiseInterface;

    /**
     * Returns the members of the set resulting from the union of all the given sets.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to union.
     *
     * @return PromiseInterface<array<int, string>> Array of members in the resulting unioned set.
     */
    public function sunion(string|array $keys, string ...$moreKeys): PromiseInterface;

    /**
     * Returns the members of the set resulting from the difference between the first set and all the successive sets.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to diff against.
     *
     * @return PromiseInterface<array<int, string>> Array of members in the resulting differenced set.
     */
    public function sdiff(string|array $keys, string ...$moreKeys): PromiseInterface;

    /**
     * Iterates members of a Set type.
     *
     * @param string $key The set key.
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     *
     * @return PromiseInterface<array{0: string, 1: array<int, string>}>
     */
    public function sscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): PromiseInterface;

    /**
     * Asynchronously streams members of a Set using SSCAN with automatic pre-fetching and backpressure.
     *
     * @param string $key The set key.
     * @param string|null $match Glob-style pattern to match member names against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     *
     * @return PromiseInterface<ScanStreamInterface<int, string>> Yields set members.
     */
    public function sscanStream(string $key, ?string $match = null, ?int $count = null): PromiseInterface;
}
