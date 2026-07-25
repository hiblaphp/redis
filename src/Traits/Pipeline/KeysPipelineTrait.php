<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Keys\DelCommand;
use Hibla\Redis\Command\Keys\ExistsCommand;
use Hibla\Redis\Command\Keys\ExpireCommand;
use Hibla\Redis\Command\Keys\PersistCommand;
use Hibla\Redis\Command\Keys\RenameCommand;
use Hibla\Redis\Command\Keys\RenamenxCommand;
use Hibla\Redis\Command\Keys\ScanCommand;
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

    /**
     * Adds a SCAN command to the pipeline.
     *
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     * @param string|null $type Filter keys by type.
     *
     * @return self For method chaining.
     */
    public function scan(string|int $cursor = '0', ?string $match = null, ?int $count = null, ?string $type = null): self
    {
        $args = [(string) $cursor];

        if ($match !== null) {
            $args[] = 'MATCH';
            $args[] = $match;
        }

        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        if ($type !== null) {
            $args[] = 'TYPE';
            $args[] = $type;
        }

        return $this->executeCommand(new ScanCommand($args));
    }

    /**
     * Adds a RENAME command to the pipeline.
     *
     * @param string $key The key to rename.
     * @param string $newKey The new name for the key.
     *
     * @return self For method chaining.
     */
    public function rename(string $key, string $newKey): self
    {
        return $this->executeCommand(new RenameCommand([$key, $newKey]));
    }

    /**
     * Adds a RENAMENX command to the pipeline.
     *
     * @param string $key The key to rename.
     * @param string $newKey The new name for the key.
     *
     * @return self For method chaining.
     */
    public function renamenx(string $key, string $newKey): self
    {
        return $this->executeCommand(new RenamenxCommand([$key, $newKey]));
    }

    /**
     * Adds a PERSIST command to the pipeline.
     *
     * @param string $key Target key.
     *
     * @return self For method chaining.
     */
    public function persist(string $key): self
    {
        return $this->executeCommand(new PersistCommand([$key]));
    }
}
