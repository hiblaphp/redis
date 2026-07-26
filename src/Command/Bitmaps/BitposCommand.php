<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Bitmaps;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis BITPOS command.
 *
 * Return the position of the first bit set to 1 or 0 in a string.
 *
 * @see https://redis.io/commands/bitpos/
 *
 * @extends AbstractCommand<int>
 */
final class BitposCommand extends AbstractCommand
{
    public string $id {
        get => 'BITPOS';
    }
}
