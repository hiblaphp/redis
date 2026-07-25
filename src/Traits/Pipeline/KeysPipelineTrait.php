<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Keys\DelCommand;
use Hibla\Redis\Command\Keys\ExistsCommand;
use Hibla\Redis\Command\Keys\ExpireCommand;
use Hibla\Redis\Command\Keys\TtlCommand;
use Hibla\Redis\Command\Keys\TypeCommand;
use Hibla\Redis\Command\Keys\UnlinkCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait KeysPipelineTrait
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
     * Adds a DEL command to the pipeline.
     *
     * @param string ...$keys One or more keys to delete.
     *
     * @return self For method chaining.
     */
    public function del(string ...$keys): self
    {
        return $this->executeCommand(new DelCommand($keys));
    }

    /**
     * Adds an EXISTS command to the pipeline.
     *
     * @param string ...$keys One or more keys to check.
     *
     * @return self For method chaining.
     */
    public function exists(string ...$keys): self
    {
        return $this->executeCommand(new ExistsCommand($keys));
    }

    /**
     * Adds an EXPIRE command to the pipeline.
     *
     * @param string $key The target key.
     * @param int $seconds Timeout in seconds.
     *
     * @return self For method chaining.
     */
    public function expire(string $key, int $seconds): self
    {
        return $this->executeCommand(new ExpireCommand([$key, $seconds]));
    }

    /**
     * Adds a TTL command to the pipeline.
     *
     * @param string $key The key to inspect.
     *
     * @return self For method chaining.
     */
    public function ttl(string $key): self
    {
        return $this->executeCommand(new TtlCommand([$key]));
    }

    /**
     * Adds a TYPE command to the pipeline.
     *
     * @param string $key The key to inspect.
     *
     * @return self For method chaining.
     */
    public function type(string $key): self
    {
        return $this->executeCommand(new TypeCommand([$key]));
    }

    /**
     * Adds an UNLINK command to the pipeline.
     *
     * @param string ...$keys One or more keys to unlink.
     *
     * @return self For method chaining.
     */
    public function unlink(string ...$keys): self
    {
        return $this->executeCommand(new UnlinkCommand($keys));
    }
}
