<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;

interface StreamsCommandsInterface
{
    /**
     * Appends the specified entry to the stream stored at key.
     *
     * @param string $key Target stream key.
     * @param array<string, mixed> $values Associative field/value payload to store.
     * @param string $id Explicit entry ID or '*' for auto-generation.
     * @param int|null $maxLen Optional maximum stream length for auto-trimming.
     * @param bool $approximate Whether to perform approximate (~ ) trimming.
     *
     * @return PromiseInterface<string> Resolves to assigned entry ID string.
     */
    public function xadd(string $key, array $values, string $id = '*', ?int $maxLen = null, bool $approximate = true): PromiseInterface;

    /**
     * Returns the number of entries in a stream.
     *
     * @param string $key Stream key.
     *
     * @return PromiseInterface<int> Number of entries in stream.
     */
    public function xlen(string $key): PromiseInterface;

    /**
     * Returns stream entries matching the specified range of IDs.
     *
     * @param string $key Stream key.
     * @param string $start Start ID ('-' for minimum).
     * @param string $end End ID ('+' for maximum).
     * @param int|null $count Maximum number of entries to return.
     *
     * @return PromiseInterface<array<string, array<string, string>>> Map of `[entry_id => [field => value]]`.
     */
    public function xrange(string $key, string $start = '-', string $end = '+', ?int $count = null): PromiseInterface;

    /**
     * Returns stream entries matching a range of IDs in reverse order.
     *
     * @param string $key Stream key.
     * @param string $end End ID ('+' for maximum).
     * @param string $start Start ID ('-' for minimum).
     * @param int|null $count Maximum number of entries to return.
     *
     * @return PromiseInterface<array<string, array<string, string>>> Map of `[entry_id => [field => value]]`.
     */
    public function xrevrange(string $key, string $end = '+', string $start = '-', ?int $count = null): PromiseInterface;

    /**
     * Removes specified entries from a stream.
     *
     * @param string $key Stream key.
     * @param string ...$ids One or more entry IDs to remove.
     *
     * @return PromiseInterface<int> Number of entries deleted.
     */
    public function xdel(string $key, string ...$ids): PromiseInterface;

    /**
     * Trims a stream to a maximum number of items.
     *
     * @param string $key Stream key.
     * @param int $maxLen Maximum number of entries to keep.
     * @param bool $approximate Whether to use approximate trimming.
     *
     * @return PromiseInterface<int> Number of entries removed.
     */
    public function xtrim(string $key, int $maxLen, bool $approximate = true): PromiseInterface;

    /**
     * Reads entries from one or multiple streams.
     *
     * @param array<string, string> $streams Associative array of `['stream_key' => 'last_id']`.
     * @param int|null $count Maximum number of entries to read per stream.
     * @param int|null $block Milliseconds to block if no data is available (0 = block indefinitely).
     *
     * @return PromiseInterface<array<string, array<string, array<string, string>>>|null> Map of `[streamKey => [entry_id => [field => value]]]`.
     */
    public function xread(array $streams, ?int $count = null, ?int $block = null): PromiseInterface;

    /**
     * Acknowledges pending stream entries for a consumer group.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string ...$ids Entry IDs to acknowledge.
     *
     * @return PromiseInterface<int> Number of entries acknowledged.
     */
    public function xack(string $key, string $group, string ...$ids): PromiseInterface;

    /**
     * Creates a consumer group for a stream.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string $id Last delivered entry ID ('0' or '$').
     * @param bool $mkstream Automatically create the stream if missing.
     *
     * @return PromiseInterface<mixed> Resolves on success.
     */
    public function xgroupCreate(string $key, string $group, string $id = '$', bool $mkstream = false): PromiseInterface;

    /**
     * Reads entries from a stream as part of a consumer group.
     *
     * @param string $group Consumer group name.
     * @param string $consumer Consumer name.
     * @param array<string, string> $streams Associative array of `['stream_key' => 'id']` (e.g. `['s1' => '>']`).
     * @param int|null $count Maximum entries to read.
     * @param int|null $block Milliseconds to block.
     * @param bool $noack Skip adding to PEL.
     *
     * @return PromiseInterface<array<string, array<string, array<string, string>>>|null> Map of `[streamKey => [entry_id => [field => value]]]`.
     */
    public function xreadgroup(string $group, string $consumer, array $streams, ?int $count = null, ?int $block = null, bool $noack = false): PromiseInterface;

    /**
     * Inspects the list of pending messages for a consumer group.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string ...$options Optional arguments (e.g. IDLE, start, end, count, consumer).
     *
     * @return PromiseInterface<mixed> Resolves to pending message information.
     */
    public function xpending(string $key, string $group, string ...$options): PromiseInterface;

    /**
     * Changes the ownership of a pending message to the specified consumer.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string $consumer Target consumer name.
     * @param int $minIdleTime Minimum idle time in milliseconds.
     * @param array<int, string> $ids Array of entry IDs to claim.
     * @param string ...$options Additional options (IDLE, TIME, RETRYCOUNT, FORCE, JUSTID).
     *
     * @return PromiseInterface<mixed> Resolves to the claimed messages or their IDs.
     */
    public function xclaim(string $key, string $group, string $consumer, int $minIdleTime, array $ids, string ...$options): PromiseInterface;

    /**
     * Automatically fetches and claims pending messages for a consumer group.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string $consumer Target consumer name.
     * @param int $minIdleTime Minimum idle time in milliseconds.
     * @param string $start Start ID (e.g. '0-0').
     * @param string ...$options Additional options (COUNT, JUSTID).
     *
     * @return PromiseInterface<mixed> Resolves to an array containing the next start ID and the claimed messages.
     */
    public function xautoclaim(string $key, string $group, string $consumer, int $minIdleTime, string $start, string ...$options): PromiseInterface;
}
