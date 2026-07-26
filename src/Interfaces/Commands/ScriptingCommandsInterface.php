<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;

interface ScriptingCommandsInterface
{
    /**
     * Evaluates a Lua script server-side.
     *
     * @param string $script The Lua script to execute.
     * @param array<int, string> $keys Array of Redis keys accessed by the script (mapped to KEYS array in Lua).
     * @param array<int, mixed> $args Array of additional arguments (mapped to ARGV array in Lua).
     *
     * @return PromiseInterface<mixed> Resolves to the script's return value.
     */
    public function eval(string $script, array $keys = [], array $args = []): PromiseInterface;

    /**
     * Evaluates a Lua script cached on the server-side by its SHA1 digest.
     *
     * @param string $sha1 The SHA1 digest of the script.
     * @param array<int, string> $keys Array of Redis keys accessed by the script.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return PromiseInterface<mixed> Resolves to the script's return value.
     */
    public function evalsha(string $sha1, array $keys = [], array $args = []): PromiseInterface;

    /**
     * Evaluates a read-only Lua script server-side (Redis 7.0+).
     *
     * @param string $script The Lua script to execute.
     * @param array<int, string> $keys Array of Redis keys accessed by the script.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return PromiseInterface<mixed> Resolves to the script's return value.
     */
    public function evalRo(string $script, array $keys = [], array $args = []): PromiseInterface;

    /**
     * Evaluates a read-only Lua script cached by its SHA1 digest (Redis 7.0+).
     *
     * @param string $sha1 The SHA1 digest of the script.
     * @param array<int, string> $keys Array of Redis keys accessed by the script.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return PromiseInterface<mixed> Resolves to the script's return value.
     */
    public function evalshaRo(string $sha1, array $keys = [], array $args = []): PromiseInterface;

    /**
     * Loads a script into the scripts cache, without executing it.
     *
     * @param string $script The Lua script to cache.
     *
     * @return PromiseInterface<string> Resolves to the SHA1 digest of the script.
     */
    public function scriptLoad(string $script): PromiseInterface;

    /**
     * Returns information about the existence of the scripts in the script cache.
     *
     * @param string|array<int, string> $sha1s First SHA1 digest or array of SHA1 digests.
     * @param string ...$moreSha1s Additional SHA1 digests.
     *
     * @return PromiseInterface<array<int, int>> Array of integers (1 if exists, 0 if not).
     */
    public function scriptExists(string|array $sha1s, string ...$moreSha1s): PromiseInterface;

    /**
     * Flushes the Lua scripts cache.
     *
     * @param string|null $mode Optional flush mode: 'ASYNC' or 'SYNC' (Redis 6.2+).
     *
     * @return PromiseInterface<string> Resolves to "OK".
     */
    public function scriptFlush(?string $mode = null): PromiseInterface;

    /**
     * Kills the currently executing Lua script, assuming no write operation was yet performed.
     *
     * @return PromiseInterface<string> Resolves to "OK".
     */
    public function scriptKill(): PromiseInterface;

    /**
     * Sets the debug mode for subsequent scripts executed with EVAL.
     *
     * @param string $mode Debug mode ('YES', 'SYNC', or 'NO').
     *
     * @return PromiseInterface<string> Resolves to "OK".
     */
    public function scriptDebug(string $mode): PromiseInterface;
}
