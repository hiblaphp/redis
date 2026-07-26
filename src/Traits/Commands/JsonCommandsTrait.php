<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
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

trait JsonCommandsTrait
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
     * Sets the JSON value at key and path.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (e.g., '$' for root or '$.user.profile').
     * @param mixed $value PHP value to store (will be automatically serialized with json_encode).
     * @param string|null $exist Optional condition flag: 'NX' (set only if path does not exist) or 'XX' (set only if path exists).
     *
     * @return PromiseInterface<string|null> Resolves to "OK" on success, or null if 'NX'/'XX' condition was not met.
     */
    public function jsonSet(string $key, string $path, mixed $value, ?string $exist = null): PromiseInterface
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        $args = [$key, $path, $encoded];

        if ($exist !== null) {
            $args[] = strtoupper($exist);
        }

        return $this->executeCommand(new JsonSetCommand($args));
    }

    /**
     * Retrieves the JSON value at key and optional path(s).
     *
     * Automatically deserializes raw JSON response strings into native PHP types/arrays.
     *
     * @param string $key Target key storing the JSON document.
     * @param string ...$paths One or more JSONPath expressions (defaults to '$' for root document).
     *
     * @return PromiseInterface<mixed> Resolves to the decoded PHP value/array, or null if key/path does not exist.
     */
    public function jsonGet(string $key, string ...$paths): PromiseInterface
    {
        $args = [$key, ...($paths === [] ? ['$'] : $paths)];

        return $this->executeCommand(new JsonGetCommand($args));
    }

    /**
     * Deletes a JSON value at key and path.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression specifying the element/property to delete (defaults to '$').
     *
     * @return PromiseInterface<int> Resolves to the integer number of deleted JSON elements.
     */
    public function jsonDel(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonDelCommand([$key, $path]));
    }

    /**
     * Retrieves the JSON values at path for multiple keys.
     *
     * @param array<int, string> $keys Array of target keys to inspect.
     * @param string $path JSONPath expression to evaluate on each key.
     *
     * @return PromiseInterface<array<int, mixed>> Resolves to an array of decoded JSON values in the same order as requested keys.
     */
    public function jsonMget(array $keys, string $path): PromiseInterface
    {
        return $this->executeCommand(new JsonMgetCommand([...$keys, $path]));
    }

    /**
     * Increments the numeric value stored at path by the specified number.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting numeric value(s).
     * @param float|int $number Numeric amount to increment by.
     *
     * @return PromiseInterface<mixed> Resolves to the updated numeric value(s) decoded from JSON.
     */
    public function jsonNumincrby(string $key, string $path, float|int $number): PromiseInterface
    {
        return $this->executeCommand(new JsonNumincrbyCommand([$key, $path, $number]));
    }

    /**
     * Appends JSON value(s) to the array stored at path.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting an array.
     * @param mixed ...$values One or more PHP values to append (automatically serialized to JSON).
     *
     * @return PromiseInterface<array<int, int|null>|int|null> Resolves to array length(s) after appending, or null for non-array paths.
     */
    public function jsonArrappend(string $key, string $path, mixed ...$values): PromiseInterface
    {
        $encoded = array_map(static fn (mixed $v) => json_encode($v, JSON_THROW_ON_ERROR), $values);

        return $this->executeCommand(new JsonArrappendCommand([$key, $path, ...$encoded]));
    }

    /**
     * Reports the type of JSON value stored at path.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return PromiseInterface<array<int, string|null>|string|null> Resolves to type string(s) (e.g. 'object', 'array', 'string', 'integer', 'boolean').
     */
    public function jsonType(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonTypeCommand([$key, $path]));
    }

    /**
     * Toggles a boolean value stored at path.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting boolean value(s) (defaults to '$').
     *
     * @return PromiseInterface<array<int, int|bool|null>|int|bool|null> Resolves to the new boolean state(s) after toggling.
     */
    public function jsonToggle(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonToggleCommand([$key, $path]));
    }

    /**
     * Clears container values (arrays or objects) or resets numbers to zero at path.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression specifying elements to clear (defaults to '$').
     *
     * @return PromiseInterface<int> Resolves to the number of cleared containers or zeroed numbers.
     */
    public function jsonClear(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonClearCommand([$key, $path]));
    }

    /**
     * Reports the length of the JSON array at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return PromiseInterface<array<int, int|null>|int|null> Array lengths.
     */
    public function jsonArrlen(string $key, string $path = '$'): PromiseInterface
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
     * @return PromiseInterface<mixed> The popped JSON value, decoded.
     */
    public function jsonArrpop(string $key, string $path = '$', int $index = -1): PromiseInterface
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
     * @return PromiseInterface<array<int, int|null>|int|null> The index of the element, or -1 if not found.
     */
    public function jsonArrindex(string $key, string $path, mixed $value, int $start = 0, int $stop = 0): PromiseInterface
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
     * @return PromiseInterface<array<int, mixed>|null> Array of object keys.
     */
    public function jsonObjkeys(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonObjkeysCommand([$key, $path]));
    }

    /**
     * Reports the number of keys in the JSON object at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return PromiseInterface<array<int, int|null>|int|null> Number of object keys.
     */
    public function jsonObjlen(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonObjlenCommand([$key, $path]));
    }
}
