<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Internals\ScanStream;

interface KeysCommandsInterface
{
    /**
     * Removes the specified keys. A key is ignored if it does not exist.
     *
     * @param string ...$keys One or more keys to delete.
     *
     * @return PromiseInterface<int> Resolves to the number of keys removed.
     */
    public function del(string ...$keys): PromiseInterface;

    /**
     * Returns the number of keys that exist among the requested keys.
     *
     * @param string ...$keys One or more keys to check.
     *
     * @return PromiseInterface<int> Resolves to the count of existing keys.
     */
    public function exists(string ...$keys): PromiseInterface;

    /**
     * Sets a timeout on key in seconds.
     *
     * @param string $key Target key.
     * @param int $seconds Timeout in seconds.
     *
     * @return PromiseInterface<int> Resolves to 1 if timeout was set, 0 if key missing.
     */
    public function expire(string $key, int $seconds): PromiseInterface;

    /**
     * Returns the remaining time to live of a key in seconds.
     *
     * @param string $key Key to inspect.
     *
     * @return PromiseInterface<int> TTL in seconds, -1 if no TTL, -2 if missing.
     */
    public function ttl(string $key): PromiseInterface;

    /**
     * Returns the string representation of the type of value stored at key.
     *
     * @param string $key Key to inspect.
     *
     * @return PromiseInterface<string> Resolves to type string ('string', 'list', 'hash', etc.).
     */
    public function type(string $key): PromiseInterface;

    /**
     * Asynchronously deletes keys in a background thread without blocking the server.
     *
     * @param string ...$keys One or more keys to unlink.
     *
     * @return PromiseInterface<int> Resolves to number of unlinked keys.
     */
    public function unlink(string ...$keys): PromiseInterface;

    /**
     * Iterates the set of keys in the currently selected Redis database.
     *
     * @param string|int $cursor The cursor to start the scan from (use '0' for a new scan).
     * @param string|null $match Glob-style pattern to match keys against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     * @param string|null $type Filter keys by type (e.g., 'string', 'hash', 'list').
     *
     * @return PromiseInterface<array{0: string, 1: array<int, string>}> Resolves to `[next_cursor, [key1, key2, ...]]`.
     */
    public function scan(string|int $cursor = '0', ?string $match = null, ?int $count = null, ?string $type = null): PromiseInterface;

    /**
     * Renames a key. Overwrites the destination key if it already exists.
     *
     * @param string $key The key to rename.
     * @param string $newKey The new name for the key.
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function rename(string $key, string $newKey): PromiseInterface;

    /**
     * Renames a key, only if the new key does not exist.
     *
     * @param string $key The key to rename.
     * @param string $newKey The new name for the key.
     *
     * @return PromiseInterface<int> Resolves to 1 if key was renamed, 0 if new key already existed.
     */
    public function renamenx(string $key, string $newKey): PromiseInterface;

    /**
     * Removes the existing timeout on a key, turning the key from volatile to persistent.
     *
     * @param string $key Target key.
     *
     * @return PromiseInterface<int> Resolves to 1 if timeout was removed, 0 if key does not exist or has no associated timeout.
     */
    public function persist(string $key): PromiseInterface;

    /**
     * Asynchronously streams keys using SCAN with automatic pre-fetching and backpressure.
     *
     * @param string|null $match Glob-style pattern to match keys against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     * @param string|null $type Filter keys by type.
     *
     * @return PromiseInterface<ScanStream<int, string>>
     */
    public function scanStream(?string $match = null, ?int $count = null, ?string $type = null): PromiseInterface;
}
