<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface SortedSetsPipelineInterface
{
    /**
     * Adds a ZADD command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param array<string, float|int> $members Associative array of `['member' => score]`.
     *
     * @return self For method chaining.
     */
    public function zadd(string $key, array $members): self;

    /**
     * Adds a ZREM command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param string ...$members Members to remove.
     *
     * @return self For method chaining.
     */
    public function zrem(string $key, string ...$members): self;

    /**
     * Adds a ZRANGE command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param int|string $start Start range.
     * @param int|string $stop Stop range.
     *
     * @return self For method chaining.
     */
    public function zrange(string $key, int|string $start, int|string $stop): self;

    /**
     * Adds a ZSCORE command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param string $member Member name.
     *
     * @return self For method chaining.
     */
    public function zscore(string $key, string $member): self;

    /**
     * Adds a ZINCRBY command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param float|int $increment Amount to increment by.
     * @param string $member Member name.
     *
     * @return self For method chaining.
     */
    public function zincrby(string $key, float|int $increment, string $member): self;

    /**
     * Adds a ZCOUNT command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param int|string $min Minimum score.
     * @param int|string $max Maximum score.
     *
     * @return self For method chaining.
     */
    public function zcount(string $key, int|string $min, int|string $max): self;

    /**
     * Adds a ZRANK command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Return score along with the rank.
     *
     * @return self For method chaining.
     */
    public function zrank(string $key, string $member, bool $withScore = false): self;

    /**
     * Adds a ZREVRANK command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Return score along with the rank.
     *
     * @return self For method chaining.
     */
    public function zrevrank(string $key, string $member, bool $withScore = false): self;

    /**
     * Adds a BZPOPMIN command to the pipeline.
     *
     * @param string|array<string> $keys Target key(s).
     * @param float|int $timeout Block timeout in seconds.
     *
     * @return self For method chaining.
     */
    public function bzpopmin(string|array $keys, float|int $timeout = 0): self;

    /**
     * Adds a BZPOPMAX command to the pipeline.
     *
     * @param string|array<string> $keys Target key(s).
     * @param float|int $timeout Block timeout in seconds.
     *
     * @return self For method chaining.
     */
    public function bzpopmax(string|array $keys, float|int $timeout = 0): self;

    /**
     * Adds a ZSCAN command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     *
     * @return self For method chaining.
     */
    public function zscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): self;
}
