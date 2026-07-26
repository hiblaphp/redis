<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Bitmaps;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis BITCOUNT command.
 *
 * Count the number of set bits (population counting) in a string.
 *
 * @see https://redis.io/commands/bitcount/
 *
 * @extends AbstractCommand<int>
 */
final class BitcountCommand extends AbstractCommand
{
    public string $id {
        get => 'BITCOUNT';
    }
}
