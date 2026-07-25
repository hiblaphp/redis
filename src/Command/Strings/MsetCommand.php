<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Strings;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis MSET command.
 *
 * Sets multiple keys to multiple values.
 *
 * @see https://redis.io/commands/mset/
 *
 * @extends AbstractCommand<string>
 */
final class MsetCommand extends AbstractCommand
{
    public string $id {
        get => 'MSET';
    }
}
