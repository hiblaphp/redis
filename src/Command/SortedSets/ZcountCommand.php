<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\SortedSets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis ZCOUNT command.
 *
 * Returns the number of members in a sorted set with scores within the given values.
 *
 * @see https://redis.io/commands/zcount/
 *
 * @extends AbstractCommand<int>
 */
final class ZcountCommand extends AbstractCommand
{
    public string $id {
        get => 'ZCOUNT';
    }
}
