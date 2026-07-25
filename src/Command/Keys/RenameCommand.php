<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Keys;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis RENAME command.
 *
 * Renames a key.
 *
 * @see https://redis.io/commands/rename/
 *
 * @extends AbstractCommand<string>
 */
final class RenameCommand extends AbstractCommand
{
    public string $id {
        get => 'RENAME';
    }
}
