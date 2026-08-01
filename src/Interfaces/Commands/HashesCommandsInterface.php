<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Interfaces\ScanStreamInterface;

interface HashesCommandsInterface
{
    /**
     * Returns the value associated with field in the hash stored at key.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     *
     * @return PromiseInterface<string|null> Field value or null if missing.
     */
    public function hget(string $key, string $field): PromiseInterface;

    /**
     * Sets specified fields to values in hash stored at key.
     *
     * @param string $key Hash key.
     * @param array<string, mixed> $fieldsAndValues Associative array of field/value pairs.
     *
     * @return PromiseInterface<int> Number of fields added.
     */
    public function hset(string $key, array $fieldsAndValues): PromiseInterface;

    /**
     * Retrieves all fields and values of hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<array<string, string>> Associative array of fields and values.
     */
    public function hgetall(string $key): PromiseInterface;

    /**
     * Removes specified fields from hash stored at key.
     *
     * @param string $key Hash key.
     * @param string ...$fields Fields to remove.
     *
     * @return PromiseInterface<int> Number of fields removed.
     */
    public function hdel(string $key, string ...$fields): PromiseInterface;

    /**
     * Returns if field exists in hash stored at key.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     *
     * @return PromiseInterface<int> 1 if field exists, 0 otherwise.
     */
    public function hexists(string $key, string $field): PromiseInterface;

    /**
     * Returns values associated with fields in hash stored at key.
     *
     * @param string $key Hash key.
     * @param string ...$fields Fields to retrieve.
     *
     * @return PromiseInterface<array<int, string|null>> Array of values matching requested fields.
     */
    public function hmget(string $key, string ...$fields): PromiseInterface;

    /**
     * Increments the number stored at field in the hash stored at key by increment.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param int $increment Integer amount to increment by.
     *
     * @return PromiseInterface<int> Resolves to the value at field after the increment operation.
     */
    public function hincrby(string $key, string $field, int $increment): PromiseInterface;

    /**
     * Increment the specified field of a hash representing a floating point number by the specified increment.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param float $increment Float amount to increment by.
     *
     * @return PromiseInterface<string> Resolves to the string representation of the new value.
     */
    public function hincrbyfloat(string $key, string $field, float $increment): PromiseInterface;

    /**
     * Returns all field names in the hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<array<int, string>> List of fields in the hash, or an empty array if the key does not exist.
     */
    public function hkeys(string $key): PromiseInterface;

    /**
     * Returns all values in the hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<array<int, string>> List of values in the hash, or an empty array if the key does not exist.
     */
    public function hvals(string $key): PromiseInterface;

    /**
     * Returns the number of fields contained in the hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<int> Number of fields in the hash, or 0 when the key does not exist.
     */
    public function hlen(string $key): PromiseInterface;

    /**
     * Iterates fields and values of a Hash type.
     *
     * @param string $key The hash key.
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     *
     * @return PromiseInterface<array{0: string, 1: array<int, string>}>
     */
    public function hscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): PromiseInterface;

    /**
     * Asynchronously streams fields and values of a Hash using HSCAN with automatic pre-fetching and backpressure.
     *
     * @param string $key The hash key.
     * @param string|null $match Glob-style pattern to match field names against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     *
     * @return PromiseInterface<ScanStreamInterface<string, string>> Yields field => value pairs.
     */
    public function hscanStream(string $key, ?string $match = null, ?int $count = null): PromiseInterface;
}
