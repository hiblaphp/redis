<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Sets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SINTER command.
 *
 * Returns the members of the set resulting from the intersection of all the given sets.
 *
 * @see https://redis.io/commands/sinter/
 *
 * @extends AbstractCommand<array<int, string>>
 */
final class SinterCommand extends AbstractCommand
{
    public string $id {
        get => 'SINTER';
    }
}
