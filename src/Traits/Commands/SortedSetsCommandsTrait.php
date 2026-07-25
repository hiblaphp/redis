<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\SortedSets\ZaddCommand;
use Hibla\Redis\Command\SortedSets\ZrangeCommand;
use Hibla\Redis\Command\SortedSets\ZremCommand;
use Hibla\Redis\Command\SortedSets\ZscoreCommand;
use Hibla\Redis\Interfaces\CommandInterface;

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
}