<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\SortedSets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis ZREVRANK command.
 *
 * Returns the rank of member in the sorted set stored at key, with the scores ordered from high to low.
 *
 * @see https://redis.io/commands/zrevrank/
 *
 * @extends AbstractCommand<mixed>
 */
final class ZrevrankCommand extends AbstractCommand
{
    public string $id {
        get => 'ZREVRANK';
    }
}
