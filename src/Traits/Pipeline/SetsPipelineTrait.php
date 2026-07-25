<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Sets\SaddCommand;
use Hibla\Redis\Command\Sets\SismemberCommand;
use Hibla\Redis\Command\Sets\SmembersCommand;
use Hibla\Redis\Command\Sets\SremCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait SetsPipelineTrait
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
     * Adds a SADD command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed ...$members Members to add.
     *
     * @return self For method chaining.
     */
    public function sadd(string $key, mixed ...$members): self
    {
        return $this->executeCommand(new SaddCommand([$key, ...$members]));
    }

    /**
     * Adds a SREM command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed ...$members Members to remove.
     *
     * @return self For method chaining.
     */
    public function srem(string $key, mixed ...$members): self
    {
        return $this->executeCommand(new SremCommand([$key, ...$members]));
    }

    /**
     * Adds a SMEMBERS command to the pipeline.
     *
     * @param string $key The set key.
     *
     * @return self For method chaining.
     */
    public function smembers(string $key): self
    {
        return $this->executeCommand(new SmembersCommand([$key]));
    }

    /**
     * Adds a SISMEMBER command to the pipeline.
     *
     * @param string $key The set key.
     * @param mixed $member Member to test.
     *
     * @return self For method chaining.
     */
    public function sismember(string $key, mixed $member): self
    {
        return $this->executeCommand(new SismemberCommand([$key, $member]));
    }
}
