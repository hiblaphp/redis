<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Json\JsonArrappendCommand;
use Hibla\Redis\Command\Json\JsonArrindexCommand;
use Hibla\Redis\Command\Json\JsonArrlenCommand;
use Hibla\Redis\Command\Json\JsonArrpopCommand;
use Hibla\Redis\Command\Json\JsonClearCommand;
use Hibla\Redis\Command\Json\JsonDelCommand;
use Hibla\Redis\Command\Json\JsonGetCommand;
use Hibla\Redis\Command\Json\JsonMgetCommand;
use Hibla\Redis\Command\Json\JsonNumincrbyCommand;
use Hibla\Redis\Command\Json\JsonObjkeysCommand;
use Hibla\Redis\Command\Json\JsonObjlenCommand;
use Hibla\Redis\Command\Json\JsonSetCommand;
use Hibla\Redis\Command\Json\JsonToggleCommand;
use Hibla\Redis\Command\Json\JsonTypeCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait JsonPipelineTrait
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
     * Adds a JSON.SET command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (e.g., '$' or '$.user.name').
     * @param mixed $value Value to store (will be automatically JSON encoded).
     * @param string|null $exist Optional condition flag: 'NX' (set if not exists) or 'XX' (set if exists).
     *
     * @return self For method chaining.
     */
    public function jsonSet(string $key, string $path, mixed $value, ?string $exist = null): self
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        $args = [$key, $path, $encoded];

        if ($exist !== null) {
            $args[] = strtoupper($exist);
        }

        return $this->executeCommand(new JsonSetCommand($args));
    }

    /**
     * Adds a JSON.GET command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string ...$paths One or more JSONPath expressions (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonGet(string $key, string ...$paths): self
    {
        $args = [$key, ...($paths === [] ? ['$'] : $paths)];

        return $this->executeCommand(new JsonGetCommand($args));
    }

    /**
     * Adds a JSON.DEL command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonDel(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonDelCommand([$key, $path]));
    }

    /**
     * Adds a JSON.MGET command to the pipeline.
     *
     * @param array<int, string> $keys Array of target keys to inspect.
     * @param string $path JSONPath expression.
     *
     * @return self For method chaining.
     */
    public function jsonMget(array $keys, string $path): self
    {
        return $this->executeCommand(new JsonMgetCommand([...$keys, $path]));
    }

    /**
     * Adds a JSON.NUMINCRBY command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting numeric value(s).
     * @param float|int $number Amount to increment by.
     *
     * @return self For method chaining.
     */
    public function jsonNumincrby(string $key, string $path, float|int $number): self
    {
        return $this->executeCommand(new JsonNumincrbyCommand([$key, $path, $number]));
    }

    /**
     * Adds a JSON.ARRAPPEND command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting an array.
     * @param mixed ...$values Values to append (will be automatically JSON encoded).
     *
     * @return self For method chaining.
     */
    public function jsonArrappend(string $key, string $path, mixed ...$values): self
    {
        $encoded = array_map(static fn (mixed $v) => json_encode($v, JSON_THROW_ON_ERROR), $values);

        return $this->executeCommand(new JsonArrappendCommand([$key, $path, ...$encoded]));
    }

    /**
     * Adds a JSON.TYPE command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonType(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonTypeCommand([$key, $path]));
    }

    /**
     * Adds a JSON.TOGGLE command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting boolean value(s) (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonToggle(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonToggleCommand([$key, $path]));
    }

    /**
     * Adds a JSON.CLEAR command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonClear(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonClearCommand([$key, $path]));
    }

    /**
     * Reports the length of the JSON array at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonArrlen(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonArrlenCommand([$key, $path]));
    }

    /**
     * Removes and returns an element from the index in the JSON array.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     * @param int $index Index to pop from (defaults to -1 for the last element).
     *
     * @return self For method chaining.
     */
    public function jsonArrpop(string $key, string $path = '$', int $index = -1): self
    {
        return $this->executeCommand(new JsonArrpopCommand([$key, $path, $index]));
    }

    /**
     * Searches for the first occurrence of a scalar JSON value in an array.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression.
     * @param mixed $value PHP value to search for (will be JSON encoded).
     * @param int $start Start index (inclusive).
     * @param int $stop Stop index (exclusive).
     *
     * @return self For method chaining.
     */
    public function jsonArrindex(string $key, string $path, mixed $value, int $start = 0, int $stop = 0): self
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        $args = [$key, $path, $encoded];

        if ($start !== 0 || $stop !== 0) {
            $args[] = $start;
            if ($stop !== 0) {
                $args[] = $stop;
            }
        }

        return $this->executeCommand(new JsonArrindexCommand($args));
    }

    /**
     * Returns the keys in the object at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonObjkeys(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonObjkeysCommand([$key, $path]));
    }

    /**
     * Reports the number of keys in the JSON object at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonObjlen(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonObjlenCommand([$key, $path]));
    }
}
