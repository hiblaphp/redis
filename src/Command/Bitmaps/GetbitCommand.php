<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Bitmaps;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis GETBIT command.
 *
 * Returns the bit value at offset in the string value stored at key.
 *
 * @see https://redis.io/commands/getbit/
 *
 * @extends AbstractCommand<int>
 */
final class GetbitCommand extends AbstractCommand
{
    public string $id {
        get => 'GETBIT';
    }
}
