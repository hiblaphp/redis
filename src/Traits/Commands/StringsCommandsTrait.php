<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Strings\DecrCommand;
use Hibla\Redis\Command\Strings\GetCommand;
use Hibla\Redis\Command\Strings\IncrbyCommand;
use Hibla\Redis\Command\Strings\IncrbyfloatCommand;
use Hibla\Redis\Command\Strings\IncrCommand;
use Hibla\Redis\Command\Strings\MgetCommand;
use Hibla\Redis\Command\Strings\SetCommand;
use Hibla\Redis\Command\Strings\SetexCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait StringsCommandsTrait
{
    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    abstract public function executeCommand(CommandInterface $command): PromiseInterface;

    /**
     * Retrieves the string value associated with the specified key.
     *
     * @param string $key Key to retrieve.
     *
     * @return PromiseInterface<string|null> Resolves to value or null if missing.
     */
    public function get(string $key): PromiseInterface
    {
        return $this->executeCommand(new GetCommand([$key]));
    }

    /**
     * Sets the specified key to hold the provided string value.
     *
     * @param string $key Key to set.
     * @param mixed $value Value to store (scalar or Stringable).
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function set(string $key, mixed $value): PromiseInterface
    {
        return $this->executeCommand(new SetCommand([$key, $value]));
    }

    /**
     * Retrieves the values of all specified keys.
     *
     * @param string ...$keys Keys to retrieve.
     *
     * @return PromiseInterface<array<int, string|null>> Array of values matching key order.
     */
    public function mget(string ...$keys): PromiseInterface
    {
        return $this->executeCommand(new MgetCommand($keys));
    }

    /**
     * Increments the number stored at key by one.
     *
     * @param string $key Key to increment.
     *
     * @return PromiseInterface<int> Updated integer value.
     */
    public function incr(string $key): PromiseInterface
    {
        return $this->executeCommand(new IncrCommand([$key]));
    }

    /**
     * Decrements the number stored at key by one.
     *
     * @param string $key Key to decrement.
     *
     * @return PromiseInterface<int> Updated integer value.
     */
    public function decr(string $key): PromiseInterface
    {
        return $this->executeCommand(new DecrCommand([$key]));
    }

    /**
     * Increments the number stored at key by increment amount.
     *
     * @param string $key Key to increment.
     * @param int $increment Integer amount to increment.
     *
     * @return PromiseInterface<int> Updated integer value.
     */
    public function incrby(string $key, int $increment): PromiseInterface
    {
        return $this->executeCommand(new IncrbyCommand([$key, $increment]));
    }

    /**
     * Increments floating point number stored at key by increment amount.
     *
     * @param string $key Key to increment.
     * @param float $increment Float amount to increment.
     *
     * @return PromiseInterface<string> Updated float value represented as a string.
     */
    public function incrbyfloat(string $key, float $increment): PromiseInterface
    {
        return $this->executeCommand(new IncrbyfloatCommand([$key, $increment]));
    }

    /**
     * Sets key to hold string value with a given timeout in seconds.
     *
     * @param string $key Key to set.
     * @param int $seconds TTL in seconds.
     * @param mixed $value Value to store.
     *
     * @return PromiseInterface<string> Resolves to "OK".
     */
    public function setex(string $key, int $seconds, mixed $value): PromiseInterface
    {
        return $this->executeCommand(new SetexCommand([$key, $seconds, $value]));
    }
}
