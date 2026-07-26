<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\SortedSets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis BZPOPMIN command.
 *
 * Removes and returns the member with the lowest score from one or more sorted sets, or blocks until one is available.
 *
 * @see https://redis.io/commands/bzpopmin/
 *
 * @extends AbstractCommand<array<int, string>|null>
 */
final class BzpopminCommand extends AbstractCommand
{
    public string $id {
        get => 'BZPOPMIN';
    }

    public function isBlocking(): bool
    {
        return true;
    }
}
