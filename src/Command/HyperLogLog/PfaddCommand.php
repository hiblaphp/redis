<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\HyperLogLog;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis PFADD command.
 *
 * Adds the specified elements to the specified HyperLogLog.
 *
 * @see https://redis.io/commands/pfadd/
 *
 * @extends AbstractCommand<int>
 */
final class PfaddCommand extends AbstractCommand
{
    public string $id {
        get => 'PFADD';
    }
}
