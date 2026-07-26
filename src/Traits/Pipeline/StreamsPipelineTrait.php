<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

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

trait StreamsPipelineTrait
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
    public function xadd(string $key, array $values, string $id = '*', ?int $maxLen = null, bool $approximate = true): self
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
     * Adds an XLEN command to the pipeline.
     *
     * @param string $key Stream key.
     *
     * @return self For method chaining.
     */
    public function xlen(string $key): self
    {
        return $this->executeCommand(new XlenCommand([$key]));
    }

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
    public function xrange(string $key, string $start = '-', string $end = '+', ?int $count = null): self
    {
        $args = [$key, $start, $end];
        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        return $this->executeCommand(new XrangeCommand($args));
    }

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
    public function xrevrange(string $key, string $end = '+', string $start = '-', ?int $count = null): self
    {
        $args = [$key, $end, $start];
        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        return $this->executeCommand(new XrevrangeCommand($args));
    }

    /**
     * Adds an XDEL command to the pipeline.
     *
     * @param string $key Stream key.
     * @param string ...$ids Entry IDs to delete.
     *
     * @return self For method chaining.
     */
    public function xdel(string $key, string ...$ids): self
    {
        return $this->executeCommand(new XdelCommand([$key, ...$ids]));
    }

    /**
     * Adds an XTRIM command to the pipeline.
     *
     * @param string $key Stream key.
     * @param int $maxLen Target max length.
     * @param bool $approximate Approximate trimming.
     *
     * @return self For method chaining.
     */
    public function xtrim(string $key, int $maxLen, bool $approximate = true): self
    {
        $args = [$key, 'MAXLEN'];
        if ($approximate) {
            $args[] = '~';
        }
        $args[] = $maxLen;

        return $this->executeCommand(new XtrimCommand($args));
    }

    /**
     * Adds an XREAD command to the pipeline.
     *
     * @param array<string, string> $streams Associative array of `['stream' => 'id']`.
     * @param int|null $count Max entries per stream.
     * @param int|null $block Milliseconds to block.
     *
     * @return self For method chaining.
     */
    public function xread(array $streams, ?int $count = null, ?int $block = null): self
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
     * Adds an XACK command to the pipeline.
     *
     * @param string $key Stream key.
     * @param string $group Group name.
     * @param string ...$ids Entry IDs.
     *
     * @return self For method chaining.
     */
    public function xack(string $key, string $group, string ...$ids): self
    {
        return $this->executeCommand(new XackCommand([$key, $group, ...$ids]));
    }

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
    public function xgroupCreate(string $key, string $group, string $id = '$', bool $mkstream = false): self
    {
        $args = ['CREATE', $key, $group, $id];
        if ($mkstream) {
            $args[] = 'MKSTREAM';
        }

        return $this->executeCommand(new XgroupCommand($args));
    }

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
    public function xreadgroup(string $group, string $consumer, array $streams, ?int $count = null, ?int $block = null, bool $noack = false): self
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
     * @return self For method chaining.
     */
    public function xpending(string $key, string $group, string ...$options): self
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
     * @return self For method chaining.
     */
    public function xclaim(string $key, string $group, string $consumer, int $minIdleTime, array $ids, string ...$options): self
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
     * @return self For method chaining.
     */
    public function xautoclaim(string $key, string $group, string $consumer, int $minIdleTime, string $start, string ...$options): self
    {
        return $this->executeCommand(new XautoclaimCommand([$key, $group, $consumer, $minIdleTime, $start, ...$options]));
    }
}
