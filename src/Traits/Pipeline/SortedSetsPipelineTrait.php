<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\SortedSets\ZaddCommand;
use Hibla\Redis\Command\SortedSets\ZrangeCommand;
use Hibla\Redis\Command\SortedSets\ZremCommand;
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
}