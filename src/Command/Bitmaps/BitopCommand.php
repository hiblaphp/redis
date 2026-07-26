<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Bitmaps;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis BITOP command.
 *
 * Perform a bitwise operation between multiple keys (containing string values) and store the result in the destination key.
 *
 * @see https://redis.io/commands/bitop/
 *
 * @extends AbstractCommand<int>
 */
final class BitopCommand extends AbstractCommand
{
    public string $id {
        get => 'BITOP';
    }
}
