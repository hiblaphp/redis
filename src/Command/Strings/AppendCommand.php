<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Strings;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis APPEND command.
 *
 * Appends the value at the end of the string.
 *
 * @see https://redis.io/commands/append/
 *
 * @extends AbstractCommand<int>
 */
final class AppendCommand extends AbstractCommand
{
    public string $id {
        get => 'APPEND';
    }
}
