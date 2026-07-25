<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XDEL command.
 *
 * Removes specified entries from a stream.
 *
 * @see https://redis.io/commands/xdel/
 *
 * @extends AbstractCommand<int>
 */
final class XdelCommand extends AbstractCommand
{
    public string $id {
        get => 'XDEL';
    }
}
