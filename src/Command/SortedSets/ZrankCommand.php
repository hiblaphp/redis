<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\SortedSets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis ZRANK command.
 *
 * Returns the rank of member in the sorted set stored at key, with the scores ordered from low to high.
 *
 * @see https://redis.io/commands/zrank/
 *
 * @extends AbstractCommand<mixed>
 */
final class ZrankCommand extends AbstractCommand
{
    public string $id {
        get => 'ZRANK';
    }
}
