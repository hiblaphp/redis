<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XLEN command.
 *
 * Returns the number of entries inside a stream.
 *
 * @see https://redis.io/commands/xlen/
 *
 * @extends AbstractCommand<int>
 */
final class XlenCommand extends AbstractCommand
{
    public string $id {
        get => 'XLEN';
    }
}
