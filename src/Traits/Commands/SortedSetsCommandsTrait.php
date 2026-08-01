<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
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
use Hibla\Redis\Interfaces\ScanStreamInterface;
use Hibla\Redis\Internals\ScanStream;

trait SortedSetsCommandsTrait
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
     * Adds members with scores to sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param array<string, float|int> $members Associative array of `['member' => score]`.
     *
     * @return PromiseInterface<int> Number of elements added.
     */
    public function zadd(string $key, array $members): PromiseInterface
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
     * Removes members from sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param string ...$members Members to remove.
     *
     * @return PromiseInterface<int> Number of elements removed.
     */
    public function zrem(string $key, string ...$members): PromiseInterface
    {
        return $this->executeCommand(new ZremCommand([$key, ...$members]));
    }

    /**
     * Returns range of elements in sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param int|string $start Start range.
     * @param int|string $stop Stop range.
     *
     * @return PromiseInterface<array<int, string>> Elements in range.
     */
    public function zrange(string $key, int|string $start, int|string $stop): PromiseInterface
    {
        return $this->executeCommand(new ZrangeCommand([$key, $start, $stop]));
    }

    /**
     * Returns score of member in sorted set stored at key.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     *
     * @return PromiseInterface<string|null> Score string or null if missing.
     */
    public function zscore(string $key, string $member): PromiseInterface
    {
        return $this->executeCommand(new ZscoreCommand([$key, $member]));
    }

    /**
     * Increments the score of a member in a sorted set.
     *
     * @param string $key Sorted set key.
     * @param float|int $increment Amount to increment by.
     * @param string $member Member name.
     *
     * @return PromiseInterface<string> Resolves to the new score of the member as a string.
     */
    public function zincrby(string $key, float|int $increment, string $member): PromiseInterface
    {
        return $this->executeCommand(new ZincrbyCommand([$key, $increment, $member]));
    }

    /**
     * Returns the number of members in a sorted set with scores within the given values.
     *
     * @param string $key Sorted set key.
     * @param int|string $min Minimum score (can be string like '-inf' or '(1').
     * @param int|string $max Maximum score (can be string like '+inf' or '(5').
     *
     * @return PromiseInterface<int> Number of elements in the specified score range.
     */
    public function zcount(string $key, int|string $min, int|string $max): PromiseInterface
    {
        return $this->executeCommand(new ZcountCommand([$key, $min, $max]));
    }

    /**
     * Returns the rank of member in the sorted set, with the scores ordered from low to high.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Whether to return the score along with the rank (Redis 6.2+).
     *
     * @return PromiseInterface<mixed> Resolves to the integer rank (0-based), array if WITHSCORE is true, or null if missing.
     */
    public function zrank(string $key, string $member, bool $withScore = false): PromiseInterface
    {
        $args = [$key, $member];
        if ($withScore) {
            $args[] = 'WITHSCORE';
        }

        return $this->executeCommand(new ZrankCommand($args));
    }

    /**
     * Returns the rank of member in the sorted set, with the scores ordered from high to low.
     *
     * @param string $key Sorted set key.
     * @param string $member Member name.
     * @param bool $withScore Whether to return the score along with the rank (Redis 6.2+).
     *
     * @return PromiseInterface<mixed> Resolves to the integer rank (0-based), array if WITHSCORE is true, or null if missing.
     */
    public function zrevrank(string $key, string $member, bool $withScore = false): PromiseInterface
    {
        $args = [$key, $member];
        if ($withScore) {
            $args[] = 'WITHSCORE';
        }

        return $this->executeCommand(new ZrevrankCommand($args));
    }

    /**
     * Removes and returns the member with the lowest score from one or more sorted sets, or blocks until one is available.
     *
     * @param string|array<string> $keys Target sorted set key(s).
     * @param float|int $timeout Maximum time to block in seconds (0 = block indefinitely).
     *
     * @return PromiseInterface<array<int, string>|null> Resolves to `[key, member, score]` array, or null on timeout.
     */
    public function bzpopmin(string|array $keys, float|int $timeout = 0): PromiseInterface
    {
        $args = \is_array($keys) ? $keys : [$keys];
        $args[] = $timeout;

        return $this->executeCommand(new BzpopminCommand($args));
    }

    /**
     * Removes and returns the member with the highest score from one or more sorted sets, or blocks until one is available.
     *
     * @param string|array<string> $keys Target sorted set key(s).
     * @param float|int $timeout Maximum time to block in seconds (0 = block indefinitely).
     *
     * @return PromiseInterface<array<int, string>|null> Resolves to `[key, member, score]` array, or null on timeout.
     */
    public function bzpopmax(string|array $keys, float|int $timeout = 0): PromiseInterface
    {
        $args = \is_array($keys) ? $keys : [$keys];
        $args[] = $timeout;

        return $this->executeCommand(new BzpopmaxCommand($args));
    }

    /**
     * Iterates members and scores of a Sorted Set type.
     *
     * @param string $key The sorted set key.
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     *
     * @return PromiseInterface<array{0: string, 1: array<int, string>}>
     */
    public function zscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): PromiseInterface
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

    /**
     * Asynchronously streams members and scores of a Sorted Set using ZSCAN.
     *
     * @param string $key The sorted set key.
     * @param string|null $match Glob-style pattern to match member names against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     *
     * @return PromiseInterface<ScanStreamInterface<string, string>> Yields member => score pairs.
     */
    public function zscanStream(string $key, ?string $match = null, ?int $count = null): PromiseInterface
    {
        $fetcher = function (string $cursor) use ($key, $match, $count): PromiseInterface {
            $args = [$key, $cursor];
            if ($match !== null) {
                $args[] = 'MATCH';
                $args[] = $match;
            }
            if ($count !== null) {
                $args[] = 'COUNT';
                $args[] = $count;
            }

            return $this->executeCommand(new ZscanCommand($args));
        };

        $resultParser = static function (array $elements): array {
            $tuples = [];
            $total = \count($elements);
            for ($i = 0; $i < $total; $i += 2) {
                if (isset($elements[$i + 1])) {
                    $rawMember = $elements[$i];
                    $rawScore = $elements[$i + 1];

                    $member = \is_scalar($rawMember) || $rawMember instanceof \Stringable ? (string) $rawMember : '';
                    $score = \is_scalar($rawScore) || $rawScore instanceof \Stringable ? (string) $rawScore : '0';

                    $tuples[] = [$member, $score];
                }
            }

            return $tuples;
        };

        /** @var Promise<ScanStream<string, string>> $streamPromise */
        $streamPromise = new Promise();

        $initialPromise = $fetcher('0');
        $initialPromise->then(
            function (array $result) use ($streamPromise, $fetcher, $resultParser): void {
                if ($streamPromise->isCancelled()) {
                    return;
                }

                $cursor = (string) $result[0];
                $elements = $resultParser($result[1]);

                $streamPromise->resolve(new ScanStream($fetcher, $resultParser, $cursor, $elements));
            },
            function (\Throwable $e) use ($streamPromise): void {
                if (! $streamPromise->isSettled()) {
                    $streamPromise->reject($e);
                }
            }
        );

        Promise::forwardCancellation($streamPromise, $initialPromise);

        return $streamPromise;
    }
}
