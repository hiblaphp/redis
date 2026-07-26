<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Bitmaps;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SETBIT command.
 *
 * Sets or clears the bit at offset in the string value stored at key.
 *
 * @see https://redis.io/commands/setbit/
 *
 * @extends AbstractCommand<int>
 */
final class SetbitCommand extends AbstractCommand
{
    public string $id {
        get => 'SETBIT';
    }
}
