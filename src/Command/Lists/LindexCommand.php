<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Lists;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis LINDEX command.
 *
 * Returns the element at index index in the list stored at key.
 *
 * @see https://redis.io/commands/lindex/
 *
 * @extends AbstractCommand<string|null>
 */
final class LindexCommand extends AbstractCommand
{
    public string $id {
        get => 'LINDEX';
    }
}
