<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Lists;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis LRANGE command.
 *
 * Returns the specified elements of the list stored at key.
 *
 * @see https://redis.io/commands/lrange/
 *
 * @extends AbstractCommand<array<int, string>>
 */
final class LrangeCommand extends AbstractCommand
{
    public string $id {
        get => 'LRANGE';
    }
}
