<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface SetsPipelineInterface
{
    /**
     * Adds a SADD command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed ...$members Members to add.
     *
     * @return self For method chaining.
     */
    public function sadd(string $key, mixed ...$members): self;

    /**
     * Adds a SREM command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed ...$members Members to remove.
     *
     * @return self For method chaining.
     */
    public function srem(string $key, mixed ...$members): self;

    /**
     * Adds a SMEMBERS command to the pipeline.
     *
     * @param string $key The set key.
     *
     * @return self For method chaining.
     */
    public function smembers(string $key): self;

    /**
     * Adds a SISMEMBER command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed $member Member to test.
     *
     * @return self For method chaining.
     */
    public function sismember(string $key, mixed $member): self;

    /**
     * Adds an SCARD command to the pipeline.
     *
     * @param string $key Set key.
     *
     * @return self For method chaining.
     */
    public function scard(string $key): self;

    /**
     * Adds an SPOP command to the pipeline.
     *
     * @param string $key Set key.
     * @param int|null $count Number of members to pop.
     *
     * @return self For method chaining.
     */
    public function spop(string $key, ?int $count = null): self;

    /**
     * Adds an SINTER command to the pipeline.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to intersect.
     *
     * @return self For method chaining.
     */
    public function sinter(string|array $keys, string ...$moreKeys): self;

    /**
     * Adds an SUNION command to the pipeline.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to union.
     *
     * @return self For method chaining.
     */
    public function sunion(string|array $keys, string ...$moreKeys): self;

    /**
     * Adds an SDIFF command to the pipeline.
     *
     * @param string|array<int, string> $keys First key or array of keys.
     * @param string ...$moreKeys Additional keys to diff against.
     *
     * @return self For method chaining.
     */
    public function sdiff(string|array $keys, string ...$moreKeys): self;
}
