<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Strings\AppendCommand;
use Hibla\Redis\Command\Strings\DecrCommand;
use Hibla\Redis\Command\Strings\GetCommand;
use Hibla\Redis\Command\Strings\IncrbyCommand;
use Hibla\Redis\Command\Strings\IncrbyfloatCommand;
use Hibla\Redis\Command\Strings\IncrCommand;
use Hibla\Redis\Command\Strings\MgetCommand;
use Hibla\Redis\Command\Strings\MsetCommand;
use Hibla\Redis\Command\Strings\SetCommand;
use Hibla\Redis\Command\Strings\SetexCommand;
use Hibla\Redis\Command\Strings\SetnxCommand;
use Hibla\Redis\Command\Strings\StrlenCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait StringsPipelineTrait
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
     * Adds a GET command to the pipeline.
     *
     * @param string $key The key to retrieve.
     *
     * @return self For method chaining.
     */
    public function get(string $key): self
    {
        return $this->executeCommand(new GetCommand([$key]));
    }

    /**
     * Adds a SET command to the pipeline.
     *
     * @param string $key The key to set.
     * @param mixed $value The value to store.
     *
     * @return self For method chaining.
     */
    public function set(string $key, mixed $value): self
    {
        return $this->executeCommand(new SetCommand([$key, $value]));
    }

    /**
     * Adds an MGET command to the pipeline.
     *
     * @param string ...$keys The keys to retrieve.
     *
     * @return self For method chaining.
     */
    public function mget(string ...$keys): self
    {
        return $this->executeCommand(new MgetCommand($keys));
    }

    /**
     * Adds an INCR command to the pipeline.
     *
     * @param string $key The key to increment.
     *
     * @return self For method chaining.
     */
    public function incr(string $key): self
    {
        return $this->executeCommand(new IncrCommand([$key]));
    }

    /**
     * Adds a DECR command to the pipeline.
     *
     * @param string $key The key to decrement.
     *
     * @return self For method chaining.
     */
    public function decr(string $key): self
    {
        return $this->executeCommand(new DecrCommand([$key]));
    }

    /**
     * Adds an INCRBY command to the pipeline.
     *
     * @param string $key The key to increment.
     * @param int $increment The integer amount to increment by.
     *
     * @return self For method chaining.
     */
    public function incrby(string $key, int $increment): self
    {
        return $this->executeCommand(new IncrbyCommand([$key, $increment]));
    }

    /**
     * Adds an INCRBYFLOAT command to the pipeline.
     *
     * @param string $key The key to increment.
     * @param float $increment The float amount to increment by.
     *
     * @return self For method chaining.
     */
    public function incrbyfloat(string $key, float $increment): self
    {
        return $this->executeCommand(new IncrbyfloatCommand([$key, $increment]));
    }

    /**
     * Adds a SETEX command to the pipeline.
     *
     * @param string $key The key to set.
     * @param int $seconds Time to live in seconds.
     * @param mixed $value The value to store.
     *
     * @return self For method chaining.
     */
    public function setex(string $key, int $seconds, mixed $value): self
    {
        return $this->executeCommand(new SetexCommand([$key, $seconds, $value]));
    }

    /**
     * Adds an MSET command to the pipeline.
     *
     * @param array<string, mixed> $keyValuePairs Associative array of `['key' => 'value']`.
     *
     * @return self For method chaining.
     */
    public function mset(array $keyValuePairs): self
    {
        $args = [];

        if (array_is_list($keyValuePairs)) {
            foreach ($keyValuePairs as $item) {
                $args[] = $item;
            }
        } else {
            foreach ($keyValuePairs as $key => $value) {
                $args[] = (string) $key;
                $args[] = $value;
            }
        }

        return $this->executeCommand(new MsetCommand($args));
    }

    /**
     * Adds a SETNX command to the pipeline.
     *
     * @param string $key Key to set.
     * @param mixed $value Value to store.
     *
     * @return self For method chaining.
     */
    public function setnx(string $key, mixed $value): self
    {
        return $this->executeCommand(new SetnxCommand([$key, $value]));
    }

    /**
     * Adds a STRLEN command to the pipeline.
     *
     * @param string $key Target key.
     *
     * @return self For method chaining.
     */
    public function strlen(string $key): self
    {
        return $this->executeCommand(new StrlenCommand([$key]));
    }

    /**
     * Adds an APPEND command to the pipeline.
     *
     * @param string $key Target key.
     * @param mixed $value Value to append.
     *
     * @return self For method chaining.
     */
    public function append(string $key, mixed $value): self
    {
        return $this->executeCommand(new AppendCommand([$key, $value]));
    }
}
