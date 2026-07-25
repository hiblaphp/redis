<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;

interface StringsCommandsInterface
{
    /**
     * Retrieves the string value associated with the specified key.
     *
     * @param string $key Key to retrieve.
     *
     * @return PromiseInterface<string|null> Resolves to value or null if missing.
     */
    public function get(string $key): PromiseInterface;

    /**
     * Sets the specified key to hold the provided string value.
     *
     * @param string $key Key to set.
     * @param mixed $value Value to store (scalar or Stringable).
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function set(string $key, mixed $value): PromiseInterface;

    /**
     * Retrieves the values of all specified keys.
     *
     * @param string ...$keys Keys to retrieve.
     *
     * @return PromiseInterface<array<int, string|null>> Array of values matching key order.
     */
    public function mget(string ...$keys): PromiseInterface;

    /**
     * Increments the number stored at key by one.
     *
     * @param string $key Key to increment.
     *
     * @return PromiseInterface<int> Updated integer value.
     */
    public function incr(string $key): PromiseInterface;

    /**
     * Decrements the number stored at key by one.
     *
     * @param string $key Key to decrement.
     *
     * @return PromiseInterface<int> Updated integer value.
     */
    public function decr(string $key): PromiseInterface;

    /**
     * Increments the number stored at key by increment amount.
     *
     * @param string $key Key to increment.
     * @param int $increment Integer amount to increment.
     *
     * @return PromiseInterface<int> Updated integer value.
     */
    public function incrby(string $key, int $increment): PromiseInterface;

    /**
     * Increments floating point number stored at key by increment amount.
     *
     * @param string $key Key to increment.
     * @param float $increment Float amount to increment.
     *
     * @return PromiseInterface<string> Updated float value represented as a string.
     */
    public function incrbyfloat(string $key, float $increment): PromiseInterface;

    /**
     * Sets key to hold string value with a given timeout in seconds.
     *
     * @param string $key Key to set.
     * @param int $seconds TTL in seconds.
     * @param mixed $value Value to store.
     *
     * @return PromiseInterface<string> Resolves to "OK".
     */
    public function setex(string $key, int $seconds, mixed $value): PromiseInterface;

    /**
     * Sets multiple keys to multiple values.
     *
     * @param array<string, mixed> $keyValuePairs Associative array of `['key' => 'value']`.
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function mset(array $keyValuePairs): PromiseInterface;

    /**
     * Set key to hold string value if key does not exist.
     *
     * @param string $key Key to set.
     * @param mixed $value Value to store.
     *
     * @return PromiseInterface<int> Resolves to 1 if the key was set, 0 if the key already existed.
     */
    public function setnx(string $key, mixed $value): PromiseInterface;

    /**
     * Returns the length of the string value stored at key.
     *
     * @param string $key Target key.
     *
     * @return PromiseInterface<int> Length of the string, or 0 if key does not exist.
     */
    public function strlen(string $key): PromiseInterface;

    /**
     * Appends the value at the end of the string.
     *
     * @param string $key Target key.
     * @param mixed $value Value to append.
     *
     * @return PromiseInterface<int> Length of the string after the append operation.
     */
    public function append(string $key, mixed $value): PromiseInterface;
}
