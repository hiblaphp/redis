<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Sets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SUNION command.
 *
 * Returns the members of the set resulting from the union of all the given sets.
 *
 * @see https://redis.io/commands/sunion/
 *
 * @extends AbstractCommand<array<int, string>>
 */
final class SunionCommand extends AbstractCommand
{
    public string $id {
        get => 'SUNION';
    }
}
