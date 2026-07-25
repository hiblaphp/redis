<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Lists\BlpopCommand;
use Hibla\Redis\Command\Lists\BrpopCommand;
use Hibla\Redis\Command\Lists\LindexCommand;
use Hibla\Redis\Command\Lists\LlenCommand;
use Hibla\Redis\Command\Lists\LpopCommand;
use Hibla\Redis\Command\Lists\LpushCommand;
use Hibla\Redis\Command\Lists\LrangeCommand;
use Hibla\Redis\Command\Lists\LtrimCommand;
use Hibla\Redis\Command\Lists\RpopCommand;
use Hibla\Redis\Command\Lists\RpushCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait ListsPipelineTrait
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
     * Adds an LPUSH command to the pipeline.
     *
     * @param string $key The list key.
     * @param mixed ...$values Values to prepend.
     *
     * @return self For method chaining.
     */
    public function lpush(string $key, mixed ...$values): self
    {
        return $this->executeCommand(new LpushCommand([$key, ...$values]));
    }

    /**
     * Adds an RPUSH command to the pipeline.
     *
     * @param string $key The list key.
     * @param mixed ...$values Values to append.
     *
     * @return self For method chaining.
     */
    public function rpush(string $key, mixed ...$values): self
    {
        return $this->executeCommand(new RpushCommand([$key, ...$values]));
    }

    /**
     * Adds an LPOP command to the pipeline.
     *
     * @param string $key The list key.
     * @param int|null $count Optional number of elements to pop.
     *
     * @return self For method chaining.
     */
    public function lpop(string $key, ?int $count = null): self
    {
        $args = $count !== null ? [$key, $count] : [$key];

        return $this->executeCommand(new LpopCommand($args));
    }

    /**
     * Adds an RPOP command to the pipeline.
     *
     * @param string $key The list key.
     * @param int|null $count Optional number of elements to pop.
     *
     * @return self For method chaining.
     */
    public function rpop(string $key, ?int $count = null): self
    {
        $args = $count !== null ? [$key, $count] : [$key];

        return $this->executeCommand(new RpopCommand($args));
    }

    /**
     * Adds an LLEN command to the pipeline.
     *
     * @param string $key The list key.
     *
     * @return self For method chaining.
     */
    public function llen(string $key): self
    {
        return $this->executeCommand(new LlenCommand([$key]));
    }

    /**
     * Adds a BLPOP command to the pipeline.
     *
     * @param string|array<string> $keys The list key(s) to pop from.
     * @param float|int $timeout Maximum time to block in seconds (0 = block indefinitely).
     *
     * @return self For method chaining.
     */
    public function blpop(string|array $keys, float|int $timeout = 0): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        $args[] = $timeout;

        return $this->executeCommand(new BlpopCommand($args));
    }

    /**
     * Adds an LRANGE command to the pipeline.
     *
     * @param string $key List key.
     * @param int $start Start offset.
     * @param int $stop Stop offset.
     *
     * @return self For method chaining.
     */
    public function lrange(string $key, int $start, int $stop): self
    {
        return $this->executeCommand(new LrangeCommand([$key, $start, $stop]));
    }

    /**
     * Adds an LTRIM command to the pipeline.
     *
     * @param string $key List key.
     * @param int $start Start offset.
     * @param int $stop Stop offset.
     *
     * @return self For method chaining.
     */
    public function ltrim(string $key, int $start, int $stop): self
    {
        return $this->executeCommand(new LtrimCommand([$key, $start, $stop]));
    }

    /**
     * Adds an LINDEX command to the pipeline.
     *
     * @param string $key List key.
     * @param int $index The zero-based index.
     *
     * @return self For method chaining.
     */
    public function lindex(string $key, int $index): self
    {
        return $this->executeCommand(new LindexCommand([$key, $index]));
    }

    /**
     * Adds a BRPOP command to the pipeline.
     *
     * @param string|array<string> $keys Target key(s) to pop from.
     * @param float|int $timeout Maximum time to block in seconds (0 = block indefinitely).
     *
     * @return self For method chaining.
     */
    public function brpop(string|array $keys, float|int $timeout = 0): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        $args[] = $timeout;

        return $this->executeCommand(new BrpopCommand($args));
    }
}
