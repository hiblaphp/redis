<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;

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
}
