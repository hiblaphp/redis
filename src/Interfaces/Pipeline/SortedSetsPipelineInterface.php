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
}