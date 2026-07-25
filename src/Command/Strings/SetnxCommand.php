<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Strings;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SETNX command.
 *
 * Set key to hold string value if key does not exist.
 *
 * @see https://redis.io/commands/setnx/
 *
 * @extends AbstractCommand<int>
 */
final class SetnxCommand extends AbstractCommand
{
    public string $id {
        get => 'SETNX';
    }
}
