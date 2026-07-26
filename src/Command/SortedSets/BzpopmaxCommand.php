<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\SortedSets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis BZPOPMAX command.
 *
 * Removes and returns the member with the highest score from one or more sorted sets, or blocks until one is available.
 *
 * @see https://redis.io/commands/bzpopmax/
 *
 * @extends AbstractCommand<array<int, string>|null>
 */
final class BzpopmaxCommand extends AbstractCommand
{
    public string $id {
        get => 'BZPOPMAX';
    }

    public function isBlocking(): bool
    {
        return true;
    }
}
