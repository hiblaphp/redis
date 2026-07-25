<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Sets\SaddCommand;
use Hibla\Redis\Command\Sets\SismemberCommand;
use Hibla\Redis\Command\Sets\SmembersCommand;
use Hibla\Redis\Command\Sets\SremCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait SetsCommandsTrait
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
     * Adds members to set stored at key.
     *
     * @param string $key Set key.
     * @param mixed ...$members Members to add.
     *
     * @return PromiseInterface<int> Number of elements added.
     */
    public function sadd(string $key, mixed ...$members): PromiseInterface
    {
        return $this->executeCommand(new SaddCommand([$key, ...$members]));
    }

    /**
     * Removes members from set stored at key.
     *
     * @param string $key Set key.
     * @param mixed ...$members Members to remove.
     *
     * @return PromiseInterface<int> Number of elements removed.
     */
    public function srem(string $key, mixed ...$members): PromiseInterface
    {
        return $this->executeCommand(new SremCommand([$key, ...$members]));
    }

    /**
     * Returns all members of set stored at key.
     *
     * @param string $key Set key.
     *
     * @return PromiseInterface<array<int, string>> Array of all set members.
     */
    public function smembers(string $key): PromiseInterface
    {
        return $this->executeCommand(new SmembersCommand([$key]));
    }

    /**
     * Returns if member belongs to set stored at key.
     *
     * @param string $key Set key.
     * @param mixed $member Member to test.
     *
     * @return PromiseInterface<int> 1 if member, 0 otherwise.
     */
    public function sismember(string $key, mixed $member): PromiseInterface
    {
        return $this->executeCommand(new SismemberCommand([$key, $member]));
    }
}