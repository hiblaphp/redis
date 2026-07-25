<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;

interface ListsCommandsInterface
{
    /**
     * Inserts values at head of list stored at key.
     *
     * @param string $key List key.
     * @param mixed ...$values Values to prepend.
     *
     * @return PromiseInterface<int> Length of list after operation.
     */
    public function lpush(string $key, mixed ...$values): PromiseInterface;

    /**
     * Inserts values at tail of list stored at key.
     *
     * @param string $key List key.
     * @param mixed ...$values Values to append.
     *
     * @return PromiseInterface<int> Length of list after operation.
     */
    public function rpush(string $key, mixed ...$values): PromiseInterface;

    /**
     * Removes and returns first element(s) of list stored at key.
     *
     * @param string $key List key.
     * @param int|null $count Number of elements to pop (default 1).
     *
     * @return PromiseInterface<string|array<int, string>|null>
     */
    public function lpop(string $key, ?int $count = null): PromiseInterface;

    /**
     * Removes and returns last element(s) of list stored at key.
     *
     * @param string $key List key.
     * @param int|null $count Number of elements to pop (default 1).
     *
     * @return PromiseInterface<string|array<int, string>|null>
     */
    public function rpop(string $key, ?int $count = null): PromiseInterface;

    /**
     * Returns length of list stored at key.
     *
     * @param string $key List key.
     *
     * @return PromiseInterface<int> Length of list.
     */
    public function llen(string $key): PromiseInterface;

    /**
     * Removes and returns first element of a list, blocking connection if empty.
     *
     * @param string|array<string> $keys Target key(s).
     * @param float|int $timeout Maximum block seconds (0 = infinite).
     *
     * @return PromiseInterface<array<int, string>|null> [key, value] or null on timeout.
     */
    public function blpop(string|array $keys, float|int $timeout = 0): PromiseInterface;

    /**
     * Returns the specified elements of the list stored at key.
     *
     * @param string $key List key.
     * @param int $start Start offset (0-based, supports negative offsets).
     * @param int $stop Stop offset (supports negative offsets).
     *
     * @return PromiseInterface<array<int, string>> Array of elements in the specified range.
     */
    public function lrange(string $key, int $start, int $stop): PromiseInterface;

    /**
     * Trims an existing list so that it will contain only the specified range of elements.
     *
     * @param string $key List key.
     * @param int $start Start offset.
     * @param int $stop Stop offset.
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function ltrim(string $key, int $start, int $stop): PromiseInterface;

    /**
     * Returns the element at the specified index in the list stored at key.
     *
     * @param string $key List key.
     * @param int $index The zero-based index (supports negative offsets like -1 for the last element).
     *
     * @return PromiseInterface<string|null> The requested element, or null if out of range.
     */
    public function lindex(string $key, int $index): PromiseInterface;

    /**
     * Removes and returns the last element of a list, blocking the connection if empty.
     *
     * @param string|array<string> $keys Target key(s) to pop from.
     * @param float|int $timeout Maximum time to block in seconds (0 = block indefinitely).
     *
     * @return PromiseInterface<array<int, string>|null> Resolves to `[key, value]`, or null on timeout.
     */
    public function brpop(string|array $keys, float|int $timeout = 0): PromiseInterface;
}
