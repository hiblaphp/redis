<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Scripting\EvalCommand;
use Hibla\Redis\Command\Scripting\EvalRoCommand;
use Hibla\Redis\Command\Scripting\EvalshaCommand;
use Hibla\Redis\Command\Scripting\EvalshaRoCommand;
use Hibla\Redis\Command\Scripting\ScriptCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait ScriptingPipelineTrait
{
    /**
     * @template TResponse
     *
     * @param CommandInterface<TResponse> $command
     *
     * @return self
     */
    abstract public function executeCommand(CommandInterface $command): self;

    /**
     * Adds an EVAL command to the pipeline.
     *
     * @param string $script The Lua script to execute.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function eval(string $script, array $keys = [], array $args = []): self
    {
        return $this->executeCommand(new EvalCommand([$script, count($keys), ...$keys, ...$args]));
    }

    /**
     * Adds an EVALSHA command to the pipeline.
     *
     * @param string $sha1 The SHA1 digest.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function evalsha(string $sha1, array $keys = [], array $args = []): self
    {
        return $this->executeCommand(new EvalshaCommand([$sha1, count($keys), ...$keys, ...$args]));
    }

    /**
     * Adds an EVAL_RO command to the pipeline.
     *
     * @param string $script The Lua script to execute.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function evalRo(string $script, array $keys = [], array $args = []): self
    {
        return $this->executeCommand(new EvalRoCommand([$script, count($keys), ...$keys, ...$args]));
    }

    /**
     * Adds an EVALSHA_RO command to the pipeline.
     *
     * @param string $sha1 The SHA1 digest.
     * @param array<int, string> $keys Array of Redis keys.
     * @param array<int, mixed> $args Array of additional arguments.
     *
     * @return self For method chaining.
     */
    public function evalshaRo(string $sha1, array $keys = [], array $args = []): self
    {
        return $this->executeCommand(new EvalshaRoCommand([$sha1, count($keys), ...$keys, ...$args]));
    }

    /**
     * Adds a SCRIPT LOAD command to the pipeline.
     *
     * @param string $script The Lua script to cache.
     *
     * @return self For method chaining.
     */
    public function scriptLoad(string $script): self
    {
        return $this->executeCommand(new ScriptCommand(['LOAD', $script]));
    }

    /**
     * Adds a SCRIPT EXISTS command to the pipeline.
     *
     * @param string|array<int, string> $sha1s First SHA1 digest or array.
     * @param string ...$moreSha1s Additional SHA1 digests.
     *
     * @return self For method chaining.
     */
    public function scriptExists(string|array $sha1s, string ...$moreSha1s): self
    {
        $args = \is_array($sha1s) ? $sha1s : [$sha1s];
        foreach ($moreSha1s as $sha1) {
            $args[] = $sha1;
        }

        return $this->executeCommand(new ScriptCommand(['EXISTS', ...$args]));
    }

    /**
     * Adds a SCRIPT FLUSH command to the pipeline.
     *
     * @param string|null $mode Optional flush mode ('ASYNC' or 'SYNC').
     *
     * @return self For method chaining.
     */
    public function scriptFlush(?string $mode = null): self
    {
        $args = ['FLUSH'];
        if ($mode !== null) {
            $args[] = strtoupper($mode);
        }

        return $this->executeCommand(new ScriptCommand($args));
    }

    /**
     * Adds a SCRIPT KILL command to the pipeline.
     *
     * @return self For method chaining.
     */
    public function scriptKill(): self
    {
        return $this->executeCommand(new ScriptCommand(['KILL']));
    }

    /**
     * Adds a SCRIPT DEBUG command to the pipeline.
     *
     * @param string $mode Debug mode ('YES', 'SYNC', or 'NO').
     *
     * @return self For method chaining.
     */
    public function scriptDebug(string $mode): self
    {
        return $this->executeCommand(new ScriptCommand(['DEBUG', strtoupper($mode)]));
    }
}
