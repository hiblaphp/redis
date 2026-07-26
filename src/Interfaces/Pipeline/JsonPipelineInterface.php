<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface JsonPipelineInterface
{
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
    public function jsonSet(string $key, string $path, mixed $value, ?string $exist = null): self;

    /**
     * Adds a JSON.GET command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string ...$paths One or more JSONPath expressions (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonGet(string $key, string ...$paths): self;

    /**
     * Adds a JSON.DEL command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonDel(string $key, string $path = '$'): self;

    /**
     * Adds a JSON.MGET command to the pipeline.
     *
     * @param array<int, string> $keys Array of target keys to inspect.
     * @param string $path JSONPath expression.
     *
     * @return self For method chaining.
     */
    public function jsonMget(array $keys, string $path): self;

    /**
     * Adds a JSON.NUMINCRBY command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting numeric value(s).
     * @param float|int $number Amount to increment by.
     *
     * @return self For method chaining.
     */
    public function jsonNumincrby(string $key, string $path, float|int $number): self;

    /**
     * Adds a JSON.ARRAPPEND command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting an array.
     * @param mixed ...$values Values to append (will be automatically JSON encoded).
     *
     * @return self For method chaining.
     */
    public function jsonArrappend(string $key, string $path, mixed ...$values): self;

    /**
     * Adds a JSON.TYPE command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonType(string $key, string $path = '$'): self;

    /**
     * Adds a JSON.TOGGLE command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression targeting boolean value(s) (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonToggle(string $key, string $path = '$'): self;

    /**
     * Adds a JSON.CLEAR command to the pipeline.
     *
     * @param string $key Target key storing the JSON document.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonClear(string $key, string $path = '$'): self;

    /**
     * Reports the length of the JSON array at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonArrlen(string $key, string $path = '$'): self;

    /**
     * Removes and returns an element from the index in the JSON array.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     * @param int $index Index to pop from (defaults to -1 for the last element).
     *
     * @return self For method chaining.
     */
    public function jsonArrpop(string $key, string $path = '$', int $index = -1): self;

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
    public function jsonArrindex(string $key, string $path, mixed $value, int $start = 0, int $stop = 0): self;

    /**
     * Returns the keys in the object at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonObjkeys(string $key, string $path = '$'): self;

    /**
     * Reports the number of keys in the JSON object at path.
     *
     * @param string $key Target JSON key.
     * @param string $path JSONPath expression (defaults to '$').
     *
     * @return self For method chaining.
     */
    public function jsonObjlen(string $key, string $path = '$'): self;
}
