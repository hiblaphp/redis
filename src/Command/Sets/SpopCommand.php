<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Sets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SPOP command.
 *
 * Removes and returns one or more random members from the set value store at key.
 *
 * @see https://redis.io/commands/spop/
 *
 * @extends AbstractCommand<string|array<int, string>|null>
 */
final class SpopCommand extends AbstractCommand
{
    public string $id {
        get => 'SPOP';
    }
}
