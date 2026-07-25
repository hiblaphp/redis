<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface StringsPipelineInterface
{
    /**
     * Adds a GET command to the pipeline.
     *
     * @param string $key The key to retrieve.
     *
     * @return self For method chaining.
     */
    public function get(string $key): self;

    /**
     * Adds a SET command to the pipeline.
     *
     * @param string $key The key to set.
     * @param mixed $value The value to store.
     *
     * @return self For method chaining.
     */
    public function set(string $key, mixed $value): self;

    /**
     * Adds an MGET command to the pipeline.
     *
     * @param string ...$keys The keys to retrieve.
     *
     * @return self For method chaining.
     */
    public function mget(string ...$keys): self;

    /**
     * Adds an INCR command to the pipeline.
     *
     * @param string $key The key to increment.
     *
     * @return self For method chaining.
     */
    public function incr(string $key): self;

    /**
     * Adds a DECR command to the pipeline.
     *
     * @param string $key The key to decrement.
     *
     * @return self For method chaining.
     */
    public function decr(string $key): self;

    /**
     * Adds an INCRBY command to the pipeline.
     *
     * @param string $key The key to increment.
     * @param int $increment The integer amount to increment by.
     *
     * @return self For method chaining.
     */
    public function incrby(string $key, int $increment): self;

    /**
     * Adds an INCRBYFLOAT command to the pipeline.
     *
     * @param string $key The key to increment.
     * @param float $increment The float amount to increment by.
     *
     * @return self For method chaining.
     */
    public function incrbyfloat(string $key, float $increment): self;

    /**
     * Adds a SETEX command to the pipeline.
     *
     * @param string $key The key to set.
     * @param int $seconds Time to live in seconds.
     * @param mixed $value The value to store.
     *
     * @return self For method chaining.
     */
    public function setex(string $key, int $seconds, mixed $value): self;

    /**
     * Adds an MSET command to the pipeline.
     *
     * @param array<string, mixed> $keyValuePairs Associative array of `['key' => 'value']`.
     *
     * @return self For method chaining.
     */
    public function mset(array $keyValuePairs): self;

    /**
     * Adds a SETNX command to the pipeline.
     *
     * @param string $key Key to set.
     * @param mixed $value Value to store.
     *
     * @return self For method chaining.
     */
    public function setnx(string $key, mixed $value): self;

    /**
     * Adds a STRLEN command to the pipeline.
     *
     * @param string $key Target key.
     *
     * @return self For method chaining.
     */
    public function strlen(string $key): self;

    /**
     * Adds an APPEND command to the pipeline.
     *
     * @param string $key Target key.
     * @param mixed $value Value to append.
     *
     * @return self For method chaining.
     */
    public function append(string $key, mixed $value): self;
}
