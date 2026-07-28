<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\SortedSets\BzpopmaxCommand;
use Hibla\Redis\Command\SortedSets\BzpopminCommand;
use Hibla\Redis\Command\SortedSets\ZaddCommand;
use Hibla\Redis\Command\SortedSets\ZcountCommand;
use Hibla\Redis\Command\SortedSets\ZincrbyCommand;
use Hibla\Redis\Command\SortedSets\ZrangeCommand;
use Hibla\Redis\Command\SortedSets\ZrankCommand;
use Hibla\Redis\Command\SortedSets\ZremCommand;
use Hibla\Redis\Command\SortedSets\ZrevrankCommand;
use Hibla\Redis\Command\SortedSets\ZscanCommand;
use Hibla\Redis\Command\SortedSets\ZscoreCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait SortedSetsPipelineTrait
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
     * Adds a ZADD command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param array<string, float|int> $members Associative array of `['member' => score]`.
     *
     * @return self For method chaining.
     */
    public function zadd(string $key, array $members): self
    {
        $args = [$key];

        if (array_is_list($members)) {
            foreach ($members as $item) {
                $args[] = $item;
            }
        } else {
            foreach ($members as $member => $score) {
                $args[] = $score;
                $args[] = (string) $member;
            }
        }

        return $this->executeCommand(new ZaddCommand($args));
    }

    /**
     * Adds a ZREM command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param string ...$members Members to remove.
     *
     * @return self For method chaining.
     */
    public function zrem(string $key, string ...$members): self
    {
        return $this->executeCommand(new ZremCommand([$key, ...$members]));
    }

    /**
     * Adds a ZRANGE command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param int|string $start Start range.
     * @param int|string $stop Stop range.
     *
     * @return self For method chaining.
     */
    public function zrange(string $key, int|string $start, int|string $stop): self
    {
        return $this->executeCommand(new ZrangeCommand([$key, $start, $stop]));
    }

    /**
     * Adds a ZSCORE command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param string $member Member name.
     *
     * @return self For method chaining.
     */
    public function zscore(string $key, string $member): self
    {
        return $this->executeCommand(new ZscoreCommand([$key, $member]));
    }

    /**
     * Adds a ZINCRBY command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param float|int $increment Amount to increment by.
     * @param string $member Member name.
     *
     * @return self For method chaining.
     */
    public function zincrby(string $key, float|int $increment, string $member): self
    {
        return $this->executeCommand(new ZincrbyCommand([$key, $increment, $member]));
    }

    /**
     * Adds a ZCOUNT command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param int|string $min Minimum score.
     * @param int|string $max Maximum score.
     *
     * @return self For method chaining.
     */
    public function zcount(string $key, int|string $min, int|string $max): self
    {
        return $this->executeCommand(new ZcountCommand([$key, $min, $max]));
    }

    /**
     * Adds a ZRANK command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Return score along with the rank.
     *
     * @return self For method chaining.
     */
    public function zrank(string $key, string $member, bool $withScore = false): self
    {
        $args = [$key, $member];
        if ($withScore) {
            $args[] = 'WITHSCORE';
        }

        return $this->executeCommand(new ZrankCommand($args));
    }

    /**
     * Adds a ZREVRANK command to the pipeline.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Return score along with the rank.
     *
     * @return self For method chaining.
     */
    public function zrevrank(string $key, string $member, bool $withScore = false): self
    {
        $args = [$key, $member];
        if ($withScore) {
            $args[] = 'WITHSCORE';
        }

        return $this->executeCommand(new ZrevrankCommand($args));
    }

    /**
     * Adds a BZPOPMIN command to the pipeline.
     *
     * @param string|array<string> $keys Target key(s).
     * @param float|int $timeout Block timeout in seconds.
     *
     * @return self For method chaining.
     */
    public function bzpopmin(string|array $keys, float|int $timeout = 0): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        $args[] = $timeout;

        return $this->executeCommand(new BzpopminCommand($args));
    }

    /**
     * Adds a BZPOPMAX command to the pipeline.
     *
     * @param string|array<string> $keys Target key(s).
     * @param float|int $timeout Block timeout in seconds.
     *
     * @return self For method chaining.
     */
    public function bzpopmax(string|array $keys, float|int $timeout = 0): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        $args[] = $timeout;

        return $this->executeCommand(new BzpopmaxCommand($args));
    }

    /**
     * Adds a ZSCAN command to the pipeline.
     *
     * @param string $key The sorted set key.
     * @param string|int $cursor The cursor to start the scan from (use '0' for a new scan).
     * @param string|null $match Glob-style pattern to match member names against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     *
     * @return self For method chaining.
     */
    public function zscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): self
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

        return $this->executeCommand(new ZscanCommand($args));
    }
}
