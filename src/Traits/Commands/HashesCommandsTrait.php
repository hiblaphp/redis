<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Hashes\HdelCommand;
use Hibla\Redis\Command\Hashes\HexistsCommand;
use Hibla\Redis\Command\Hashes\HgetallCommand;
use Hibla\Redis\Command\Hashes\HgetCommand;
use Hibla\Redis\Command\Hashes\HmgetCommand;
use Hibla\Redis\Command\Hashes\HsetCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait HashesCommandsTrait
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
     * Returns the value associated with field in the hash stored at key.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     *
     * @return PromiseInterface<string|null> Field value or null if missing.
     */
    public function hget(string $key, string $field): PromiseInterface
    {
        return $this->executeCommand(new HgetCommand([$key, $field]));
    }

    /**
     * Sets specified fields to values in hash stored at key.
     *
     * @param string $key Hash key.
     * @param string ...$fieldsAndValues Variadic field/value pairs.
     *
     * @return PromiseInterface<int> Number of fields added.
     */
    public function hset(string $key, string ...$fieldsAndValues): PromiseInterface
    {
        return $this->executeCommand(new HsetCommand([$key, ...$fieldsAndValues]));
    }

    /**
     * Retrieves all fields and values of hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<array<string, string>> Associative array of fields and values.
     */
    public function hgetall(string $key): PromiseInterface
    {
        return $this->executeCommand(new HgetallCommand([$key]));
    }

    /**
     * Removes specified fields from hash stored at key.
     *
     * @param string $key Hash key.
     * @param string ...$fields Fields to remove.
     *
     * @return PromiseInterface<int> Number of fields removed.
     */
    public function hdel(string $key, string ...$fields): PromiseInterface
    {
        return $this->executeCommand(new HdelCommand([$key, ...$fields]));
    }

    /**
     * Returns if field exists in hash stored at key.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     *
     * @return PromiseInterface<int> 1 if field exists, 0 otherwise.
     */
    public function hexists(string $key, string $field): PromiseInterface
    {
        return $this->executeCommand(new HexistsCommand([$key, $field]));
    }

    /**
     * Returns values associated with fields in hash stored at key.
     *
     * @param string $key Hash key.
     * @param string ...$fields Fields to retrieve.
     *
     * @return PromiseInterface<array<int, string|null>> Array of values matching requested fields.
     */
    public function hmget(string $key, string ...$fields): PromiseInterface
    {
        return $this->executeCommand(new HmgetCommand([$key, ...$fields]));
    }
}