<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface HashesPipelineInterface
{
    /**
     * Adds an HGET command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string $field The field name.
     *
     * @return self For method chaining.
     */
    public function hget(string $key, string $field): self;

    /**
     * Adds an HSET command to the pipeline.
     *
     * @param string $key The hash key.
     * @param array<string, mixed> $fieldsAndValues Associative array of field/value pairs.
     *
     * @return self For method chaining.
     */
    public function hset(string $key, array $fieldsAndValues): self;

    /**
     * Adds an HGETALL command to the pipeline.
     *
     * @param string $key The hash key.
     *
     * @return self For method chaining.
     */
    public function hgetall(string $key): self;

    /**
     * Adds an HDEL command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string ...$fields One or more fields to delete.
     *
     * @return self For method chaining.
     */
    public function hdel(string $key, string ...$fields): self;

    /**
     * Adds an HEXISTS command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string $field The field name to check.
     *
     * @return self For method chaining.
     */
    public function hexists(string $key, string $field): self;

    /**
     * Adds an HMGET command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string ...$fields The fields to retrieve.
     *
     * @return self For method chaining.
     */
    public function hmget(string $key, string ...$fields): self;

    /**
     * Adds an HINCRBY command to the pipeline.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param int $increment Integer amount to increment by.
     *
     * @return self For method chaining.
     */
    public function hincrby(string $key, string $field, int $increment): self;

    /**
     * Adds an HINCRBYFLOAT command to the pipeline.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param float $increment Float amount to increment by.
     *
     * @return self For method chaining.
     */
    public function hincrbyfloat(string $key, string $field, float $increment): self;

    /**
     * Adds an HKEYS command to the pipeline.
     *
     * @param string $key Hash key.
     *
     * @return self For method chaining.
     */
    public function hkeys(string $key): self;

    /**
     * Adds an HVALS command to the pipeline.
     *
     * @param string $key Hash key.
     *
     * @return self For method chaining.
     */
    public function hvals(string $key): self;

    /**
     * Adds an HLEN command to the pipeline.
     *
     * @param string $key Hash key.
     *
     * @return self For method chaining.
     */
    public function hlen(string $key): self;

    /**
     * Adds an HSCAN command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     *
     * @return self For method chaining.
     */
    public function hscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): self;
}
