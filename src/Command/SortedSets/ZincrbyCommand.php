<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\SortedSets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis ZINCRBY command.
 *
 * Increments the score of a member in a sorted set.
 *
 * @see https://redis.io/commands/zincrby/
 *
 * @extends AbstractCommand<string>
 */
final class ZincrbyCommand extends AbstractCommand
{
    public string $id {
        get => 'ZINCRBY';
    }
}
