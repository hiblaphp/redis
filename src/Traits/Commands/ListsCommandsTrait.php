<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Lists\BlpopCommand;
use Hibla\Redis\Command\Lists\LlenCommand;
use Hibla\Redis\Command\Lists\LpopCommand;
use Hibla\Redis\Command\Lists\LpushCommand;
use Hibla\Redis\Command\Lists\RpopCommand;
use Hibla\Redis\Command\Lists\RpushCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait ListsCommandsTrait
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
     * Inserts values at head of list stored at key.
     *
     * @param string $key List key.
     * @param mixed ...$values Values to prepend.
     *
     * @return PromiseInterface<int> Length of list after operation.
     */
    public function lpush(string $key, mixed ...$values): PromiseInterface
    {
        return $this->executeCommand(new LpushCommand([$key, ...$values]));
    }

    /**
     * Inserts values at tail of list stored at key.
     *
     * @param string $key List key.
     * @param mixed ...$values Values to append.
     *
     * @return PromiseInterface<int> Length of list after operation.
     */
    public function rpush(string $key, mixed ...$values): PromiseInterface
    {
        return $this->executeCommand(new RpushCommand([$key, ...$values]));
    }

    /**
     * Removes and returns first element(s) of list stored at key.
     *
     * @param string $key List key.
     * @param int|null $count Number of elements to pop (default 1).
     *
     * @return PromiseInterface<string|array<int, string>|null>
     */
    public function lpop(string $key, ?int $count = null): PromiseInterface
    {
        $args = $count !== null ? [$key, $count] : [$key];

        return $this->executeCommand(new LpopCommand($args));
    }

    /**
     * Removes and returns last element(s) of list stored at key.
     *
     * @param string $key List key.
     * @param int|null $count Number of elements to pop (default 1).
     *
     * @return PromiseInterface<string|array<int, string>|null>
     */
    public function rpop(string $key, ?int $count = null): PromiseInterface
    {
        $args = $count !== null ? [$key, $count] : [$key];

        return $this->executeCommand(new RpopCommand($args));
    }

    /**
     * Returns length of list stored at key.
     *
     * @param string $key List key.
     *
     * @return PromiseInterface<int> Length of list.
     */
    public function llen(string $key): PromiseInterface
    {
        return $this->executeCommand(new LlenCommand([$key]));
    }

    /**
     * Removes and returns first element of a list, blocking connection if empty.
     *
     * @param string|array<string> $keys Target key(s).
     * @param float|int $timeout Maximum block seconds (0 = infinite).
     *
     * @return PromiseInterface<array<int, string>|null> [key, value] or null on timeout.
     */
    public function blpop(string|array $keys, float|int $timeout = 0): PromiseInterface
    {
        $args = \is_array($keys) ? $keys : [$keys];
        $args[] = $timeout;

        return $this->executeCommand(new BlpopCommand($args));
    }
}
