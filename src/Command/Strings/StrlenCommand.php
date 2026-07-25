<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Strings;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis STRLEN command.
 *
 * Returns the length of the string value stored at key.
 *
 * @see https://redis.io/commands/strlen/
 *
 * @extends AbstractCommand<int>
 */
final class StrlenCommand extends AbstractCommand
{
    public string $id {
        get => 'STRLEN';
    }
}
