<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface StreamsPipelineInterface
{
    /**
     * Adds an XADD command to the pipeline.
     *
     * @param string $key Stream key.
     * @param array<string, mixed> $values Associative field/value pairs.
     * @param string $id Entry ID or '*'.
     * @param int|null $maxLen Optional max length.
     * @param bool $approximate Approximate trimming flag.
     *
     * @return self For method chaining.
     */
    public function xadd(string $key, array $values, string $id = '*', ?int $maxLen = null, bool $approximate = true): self;

    /**
     * Adds an XLEN command to the pipeline.
     *
     * @param string $key Stream key.
     *
     * @return self For method chaining.
     */
    public function xlen(string $key): self;

    /**
     * Adds an XRANGE command to the pipeline.
     *
     * @param string $key Stream key.
     * @param string $start Start ID.
     * @param string $end End ID.
     * @param int|null $count Max entries.
     *
     * @return self For method chaining.
     */
    public function xrange(string $key, string $start = '-', string $end = '+', ?int $count = null): self;

    /**
     * Adds an XREVRANGE command to the pipeline.
     *
     * @param string $key Stream key.
     * @param string $end End ID.
     * @param string $start Start ID.
     * @param int|null $count Max entries.
     *
     * @return self For method chaining.
     */
    public function xrevrange(string $key, string $end = '+', string $start = '-', ?int $count = null): self;

    /**
     * Adds an XDEL command to the pipeline.
     *
     * @param string $key Stream key.
     * @param string ...$ids Entry IDs to delete.
     *
     * @return self For method chaining.
     */
    public function xdel(string $key, string ...$ids): self;

    /**
     * Adds an XTRIM command to the pipeline.
     *
     * @param string $key Stream key.
     * @param int $maxLen Target max length.
     * @param bool $approximate Approximate trimming.
     *
     * @return self For method chaining.
     */
    public function xtrim(string $key, int $maxLen, bool $approximate = true): self;

    /**
     * Adds an XREAD command to the pipeline.
     *
     * @param array<string, string> $streams Associative array of `['stream' => 'id']`.
     * @param int|null $count Max entries per stream.
     * @param int|null $block Milliseconds to block.
     *
     * @return self For method chaining.
     */
    public function xread(array $streams, ?int $count = null, ?int $block = null): self;

    /**
     * Adds an XACK command to the pipeline.
     *
     * @param string $key Stream key.
     * @param string $group Group name.
     * @param string ...$ids Entry IDs.
     *
     * @return self For method chaining.
     */
    public function xack(string $key, string $group, string ...$ids): self;

    /**
     * Adds an XGROUP CREATE command to the pipeline.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string $id Initial ID.
     * @param bool $mkstream Create stream if missing.
     *
     * @return self For method chaining.
     */
    public function xgroupCreate(string $key, string $group, string $id = '$', bool $mkstream = false): self;

    /**
     * Adds an XREADGROUP command to the pipeline.
     *
     * @param string $group Group name.
     * @param string $consumer Consumer name.
     * @param array<string, string> $streams Streams array.
     * @param int|null $count Max entries.
     * @param int|null $block Milliseconds to block.
     * @param bool $noack Skip adding to PEL.
     *
     * @return self For method chaining.
     */
    public function xreadgroup(string $group, string $consumer, array $streams, ?int $count = null, ?int $block = null, bool $noack = false): self;
}
