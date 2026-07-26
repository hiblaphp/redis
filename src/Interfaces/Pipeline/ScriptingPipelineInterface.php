<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface ScriptingPipelineInterface
{
    /**
     * Adds an EVAL command to the pipeline.
     *
     * @param string $script The Lua script to execute.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function eval(string $script, array $keys = [], array $args = []): self;

    /**
     * Adds an EVALSHA command to the pipeline.
     *
     * @param string $sha1 The SHA1 digest.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function evalsha(string $sha1, array $keys = [], array $args = []): self;

    /**
     * Adds an EVAL_RO command to the pipeline.
     *
     * @param string $script The Lua script to execute.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function evalRo(string $script, array $keys = [], array $args = []): self;

    /**
     * Adds an EVALSHA_RO command to the pipeline.
     *
     * @param string $sha1 The SHA1 digest.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function evalshaRo(string $sha1, array $keys = [], array $args = []): self;

    /**
     * Adds a SCRIPT LOAD command to the pipeline.
     *
     * @param string $script The Lua script to cache.
     *
     * @return self For method chaining.
     */
    public function scriptLoad(string $script): self;

    /**
     * Adds a SCRIPT EXISTS command to the pipeline.
     *
     * @param string|array<int, string> $sha1s First SHA1 digest or array.
     * @param string ...$moreSha1s Additional SHA1 digests.
     *
     * @return self For method chaining.
     */
    public function scriptExists(string|array $sha1s, string ...$moreSha1s): self;

    /**
     * Adds a SCRIPT FLUSH command to the pipeline.
     *
     * @param string|null $mode Optional flush mode ('ASYNC' or 'SYNC').
     *
     * @return self For method chaining.
     */
    public function scriptFlush(?string $mode = null): self;

    /**
     * Adds a SCRIPT KILL command to the pipeline.
     *
     * @return self For method chaining.
     */
    public function scriptKill(): self;

    /**
     * Adds a SCRIPT DEBUG command to the pipeline.
     *
     * @param string $mode Debug mode ('YES', 'SYNC', or 'NO').
     *
     * @return self For method chaining.
     */
    public function scriptDebug(string $mode): self;
}
