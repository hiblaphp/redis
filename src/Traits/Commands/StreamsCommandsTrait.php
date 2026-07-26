<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Streams\XackCommand;
use Hibla\Redis\Command\Streams\XaddCommand;
use Hibla\Redis\Command\Streams\XautoclaimCommand;
use Hibla\Redis\Command\Streams\XclaimCommand;
use Hibla\Redis\Command\Streams\XdelCommand;
use Hibla\Redis\Command\Streams\XgroupCommand;
use Hibla\Redis\Command\Streams\XlenCommand;
use Hibla\Redis\Command\Streams\XpendingCommand;
use Hibla\Redis\Command\Streams\XrangeCommand;
use Hibla\Redis\Command\Streams\XreadCommand;
use Hibla\Redis\Command\Streams\XreadgroupCommand;
use Hibla\Redis\Command\Streams\XrevrangeCommand;
use Hibla\Redis\Command\Streams\XtrimCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait StreamsCommandsTrait
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
    public function xadd(string $key, array $values, string $id = '*', ?int $maxLen = null, bool $approximate = true): PromiseInterface
    {
        $args = [$key];

        if ($maxLen !== null) {
            $args[] = 'MAXLEN';
            if ($approximate) {
                $args[] = '~';
            }
            $args[] = $maxLen;
        }

        $args[] = $id;

        foreach ($values as $field => $val) {
            $args[] = (string) $field;
            $args[] = $val;
        }

        return $this->executeCommand(new XaddCommand($args));
    }

    /**
     * Returns the number of entries in a stream.
     *
     * @param string $key Stream key.
     *
     * @return PromiseInterface<int> Number of entries in stream.
     */
    public function xlen(string $key): PromiseInterface
    {
        return $this->executeCommand(new XlenCommand([$key]));
    }

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
    public function xrange(string $key, string $start = '-', string $end = '+', ?int $count = null): PromiseInterface
    {
        $args = [$key, $start, $end];
        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        return $this->executeCommand(new XrangeCommand($args));
    }

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
    public function xrevrange(string $key, string $end = '+', string $start = '-', ?int $count = null): PromiseInterface
    {
        $args = [$key, $end, $start];
        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        return $this->executeCommand(new XrevrangeCommand($args));
    }

    /**
     * Removes specified entries from a stream.
     *
     * @param string $key Stream key.
     * @param string ...$ids One or more entry IDs to remove.
     *
     * @return PromiseInterface<int> Number of entries deleted.
     */
    public function xdel(string $key, string ...$ids): PromiseInterface
    {
        return $this->executeCommand(new XdelCommand([$key, ...$ids]));
    }

    /**
     * Trims a stream to a maximum number of items.
     *
     * @param string $key Stream key.
     * @param int $maxLen Maximum number of entries to keep.
     * @param bool $approximate Whether to use approximate trimming.
     *
     * @return PromiseInterface<int> Number of entries removed.
     */
    public function xtrim(string $key, int $maxLen, bool $approximate = true): PromiseInterface
    {
        $args = [$key, 'MAXLEN'];
        if ($approximate) {
            $args[] = '~';
        }
        $args[] = $maxLen;

        return $this->executeCommand(new XtrimCommand($args));
    }

    /**
     * Reads entries from one or multiple streams.
     *
     * @param array<string, string> $streams Associative array of `['stream_key' => 'last_id']`.
     * @param int|null $count Maximum number of entries to read per stream.
     * @param int|null $block Milliseconds to block if no data is available (0 = block indefinitely).
     *
     * @return PromiseInterface<array<string, array<string, array<string, string>>>|null> Map of `[streamKey => [entry_id => [field => value]]]`.
     */
    public function xread(array $streams, ?int $count = null, ?int $block = null): PromiseInterface
    {
        $args = [];

        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        if ($block !== null) {
            $args[] = 'BLOCK';
            $args[] = $block;
        }

        $args[] = 'STREAMS';

        foreach (array_keys($streams) as $k) {
            $args[] = (string) $k;
        }

        foreach (array_values($streams) as $i) {
            $args[] = (string) $i;
        }

        return $this->executeCommand(new XreadCommand($args));
    }

    /**
     * Acknowledges pending stream entries for a consumer group.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string ...$ids Entry IDs to acknowledge.
     *
     * @return PromiseInterface<int> Number of entries acknowledged.
     */
    public function xack(string $key, string $group, string ...$ids): PromiseInterface
    {
        return $this->executeCommand(new XackCommand([$key, $group, ...$ids]));
    }

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
    public function xgroupCreate(string $key, string $group, string $id = '$', bool $mkstream = false): PromiseInterface
    {
        $args = ['CREATE', $key, $group, $id];
        if ($mkstream) {
            $args[] = 'MKSTREAM';
        }

        return $this->executeCommand(new XgroupCommand($args));
    }

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
    public function xreadgroup(string $group, string $consumer, array $streams, ?int $count = null, ?int $block = null, bool $noack = false): PromiseInterface
    {
        $args = ['GROUP', $group, $consumer];

        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        if ($block !== null) {
            $args[] = 'BLOCK';
            $args[] = $block;
        }

        if ($noack) {
            $args[] = 'NOACK';
        }

        $args[] = 'STREAMS';

        foreach (array_keys($streams) as $k) {
            $args[] = (string) $k;
        }

        foreach (array_values($streams) as $i) {
            $args[] = (string) $i;
        }

        return $this->executeCommand(new XreadgroupCommand($args));
    }

    /**
     * Inspects the list of pending messages for a consumer group.
     *
     * @param string $key Stream key.
     * @param string $group Consumer group name.
     * @param string ...$options Optional arguments (e.g. IDLE, start, end, count, consumer).
     *
     * @return PromiseInterface<mixed> Resolves to pending message information.
     */
    public function xpending(string $key, string $group, string ...$options): PromiseInterface
    {
        return $this->executeCommand(new XpendingCommand([$key, $group, ...$options]));
    }

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
    public function xclaim(string $key, string $group, string $consumer, int $minIdleTime, array $ids, string ...$options): PromiseInterface
    {
        return $this->executeCommand(new XclaimCommand([$key, $group, $consumer, $minIdleTime, ...$ids, ...$options]));
    }

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
    public function xautoclaim(string $key, string $group, string $consumer, int $minIdleTime, string $start, string ...$options): PromiseInterface
    {
        return $this->executeCommand(new XautoclaimCommand([$key, $group, $consumer, $minIdleTime, $start, ...$options]));
    }
}
