<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Keys;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis PERSIST command.
 *
 * Removes the existing timeout on key, turning the key from volatile to persistent.
 *
 * @see https://redis.io/commands/persist/
 *
 * @extends AbstractCommand<int>
 */
final class PersistCommand extends AbstractCommand
{
    public string $id {
        get => 'PERSIST';
    }
}
