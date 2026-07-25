<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Keys;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis RENAMENX command.
 *
 * Renames a key, only if the new key does not exist.
 *
 * @see https://redis.io/commands/renamenx/
 *
 * @extends AbstractCommand<int>
 */
final class RenamenxCommand extends AbstractCommand
{
    public string $id {
        get => 'RENAMENX';
    }
}
