<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Hashes\HdelCommand;
use Hibla\Redis\Command\Hashes\HexistsCommand;
use Hibla\Redis\Command\Hashes\HgetallCommand;
use Hibla\Redis\Command\Hashes\HgetCommand;
use Hibla\Redis\Command\Hashes\HincrbyCommand;
use Hibla\Redis\Command\Hashes\HincrbyfloatCommand;
use Hibla\Redis\Command\Hashes\HkeysCommand;
use Hibla\Redis\Command\Hashes\HlenCommand;
use Hibla\Redis\Command\Hashes\HmgetCommand;
use Hibla\Redis\Command\Hashes\HscanCommand;
use Hibla\Redis\Command\Hashes\HsetCommand;
use Hibla\Redis\Command\Hashes\HvalsCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait HashesPipelineTrait
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
     * Adds an HGET command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string $field The field name.
     *
     * @return self For method chaining.
     */
    public function hget(string $key, string $field): self
    {
        return $this->executeCommand(new HgetCommand([$key, $field]));
    }

    /**
     * Adds an HSET command to the pipeline.
     *
     * @param string $key The hash key.
     * @param array<string, mixed> $fieldsAndValues Associative array of field/value pairs.
     *
     * @return self For method chaining.
     */
    public function hset(string $key, array $fieldsAndValues): self
    {
        $args = [$key];

        if (array_is_list($fieldsAndValues)) {
            foreach ($fieldsAndValues as $item) {
                $args[] = $item;
            }
        } else {
            foreach ($fieldsAndValues as $field => $value) {
                $args[] = (string) $field;
                $args[] = $value;
            }
        }

        return $this->executeCommand(new HsetCommand($args));
    }

    /**
     * Adds an HGETALL command to the pipeline.
     *
     * @param string $key The hash key.
     *
     * @return self For method chaining.
     */
    public function hgetall(string $key): self
    {
        return $this->executeCommand(new HgetallCommand([$key]));
    }

    /**
     * Adds an HDEL command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string ...$fields One or more fields to delete.
     *
     * @return self For method chaining.
     */
    public function hdel(string $key, string ...$fields): self
    {
        return $this->executeCommand(new HdelCommand([$key, ...$fields]));
    }

    /**
     * Adds an HEXISTS command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string $field The field name to check.
     *
     * @return self For method chaining.
     */
    public function hexists(string $key, string $field): self
    {
        return $this->executeCommand(new HexistsCommand([$key, $field]));
    }

    /**
     * Adds an HMGET command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string ...$fields The fields to retrieve.
     *
     * @return self For method chaining.
     */
    public function hmget(string $key, string ...$fields): self
    {
        return $this->executeCommand(new HmgetCommand([$key, ...$fields]));
    }

    /**
     * Adds an HINCRBY command to the pipeline.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param int $increment Integer amount to increment by.
     *
     * @return self For method chaining.
     */
    public function hincrby(string $key, string $field, int $increment): self
    {
        return $this->executeCommand(new HincrbyCommand([$key, $field, $increment]));
    }

    /**
     * Adds an HINCRBYFLOAT command to the pipeline.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param float $increment Float amount to increment by.
     *
     * @return self For method chaining.
     */
    public function hincrbyfloat(string $key, string $field, float $increment): self
    {
        return $this->executeCommand(new HincrbyfloatCommand([$key, $field, $increment]));
    }

    /**
     * Adds an HKEYS command to the pipeline.
     *
     * @param string $key Hash key.
     *
     * @return self For method chaining.
     */
    public function hkeys(string $key): self
    {
        return $this->executeCommand(new HkeysCommand([$key]));
    }

    /**
     * Adds an HVALS command to the pipeline.
     *
     * @param string $key Hash key.
     *
     * @return self For method chaining.
     */
    public function hvals(string $key): self
    {
        return $this->executeCommand(new HvalsCommand([$key]));
    }

    /**
     * Adds an HLEN command to the pipeline.
     *
     * @param string $key Hash key.
     *
     * @return self For method chaining.
     */
    public function hlen(string $key): self
    {
        return $this->executeCommand(new HlenCommand([$key]));
    }

    /**
     * Adds an HSCAN command to the pipeline.
     *
     * @param string $key The hash key.
     * @param string|int $cursor The cursor to start the scan from (use '0' for a new scan).
     * @param string|null $match Glob-style pattern to match field names against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     *
     * @return self For method chaining.
     */
    public function hscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): self
    {
        $args = [$key, (string) $cursor];

        if ($match !== null) {
            $args[] = 'MATCH';
            $args[] = $match;
        }

        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        return $this->executeCommand(new HscanCommand($args));
    }
}
