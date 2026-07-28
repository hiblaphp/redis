<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;

interface SortedSetsCommandsInterface
{
    /**
     * Adds members with scores to sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param array<string, float|int> $members Associative array of `['member' => score]`.
     *
     * @return PromiseInterface<int> Number of elements added.
     */
    public function zadd(string $key, array $members): PromiseInterface;

    /**
     * Removes members from sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param string ...$members Members to remove.
     *
     * @return PromiseInterface<int> Number of elements removed.
     */
    public function zrem(string $key, string ...$members): PromiseInterface;

    /**
     * Returns range of elements in sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param int|string $start Start range.
     * @param int|string $stop Stop range.
     *
     * @return PromiseInterface<array<int, string>> Elements in range.
     */
    public function zrange(string $key, int|string $start, int|string $stop): PromiseInterface;

    /**
     * Returns score of member in sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     *
     * @return PromiseInterface<string|null> Score string or null if missing.
     */
    public function zscore(string $key, string $member): PromiseInterface;

    /**
     * Increments the score of a member in a sorted set.
     *
     * @param string $key Sorted set key.
     * @param float|int $increment Amount to increment by.
     * @param string $member Member name.
     *
     * @return PromiseInterface<string> Resolves to the new score of the member as a string.
     */
    public function zincrby(string $key, float|int $increment, string $member): PromiseInterface;

    /**
     * Returns the number of members in a sorted set with scores within the given values.
     *
     * @param string $key Sorted set key.
     * @param int|string $min Minimum score (can be string like '-inf' or '(1').
     * @param int|string $max Maximum score (can be string like '+inf' or '(5').
     *
     * @return PromiseInterface<int> Number of elements in the specified score range.
     */
    public function zcount(string $key, int|string $min, int|string $max): PromiseInterface;

    /**
     * Returns the rank of member in the sorted set, with the scores ordered from low to high.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Whether to return the score along with the rank (Redis 6.2+).
     *
     * @return PromiseInterface<mixed> Resolves to the integer rank (0-based), array if WITHSCORE is true, or null if missing.
     */
    public function zrank(string $key, string $member, bool $withScore = false): PromiseInterface;

    /**
     * Returns the rank of member in the sorted set, with the scores ordered from high to low.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Whether to return the score along with the rank (Redis 6.2+).
     *
     * @return PromiseInterface<mixed> Resolves to the integer rank (0-based), array if WITHSCORE is true, or null if missing.
     */
    public function zrevrank(string $key, string $member, bool $withScore = false): PromiseInterface;

    /**
     * Removes and returns the member with the lowest score from one or more sorted sets, or blocks until one is available.
     *
     * @param string|array<string> $keys Target sorted set key(s).
     * @param float|int $timeout Maximum time to block in seconds (0 = block indefinitely).
     *
     * @return PromiseInterface<array<int, string>|null> Resolves to `[key, member, score]` array, or null on timeout.
     */
    public function bzpopmin(string|array $keys, float|int $timeout = 0): PromiseInterface;

    /**
     * Removes and returns the member with the highest score from one or more sorted sets, or blocks until one is available.
     *
     * @param string|array<string> $keys Target sorted set key(s).
     * @param float|int $timeout Maximum time to block in seconds (0 = block indefinitely).
     *
     * @return PromiseInterface<array<int, string>|null> Resolves to `[key, member, score]` array, or null on timeout.
     */
    public function bzpopmax(string|array $keys, float|int $timeout = 0): PromiseInterface;

    /**
     * Iterates members and scores of a Sorted Set type.
     *
     * @param string $key The sorted set key.
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     *
     * @return PromiseInterface<array{0: string, 1: array<int, string>}>
     */
    public function zscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): PromiseInterface;
}
