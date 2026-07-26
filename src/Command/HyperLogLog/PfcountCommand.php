<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\HyperLogLog;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis PFCOUNT command.
 *
 * Returns the approximated cardinality computed by the HyperLogLog data structure stored at the specified variable(s).
 *
 * @see https://redis.io/commands/pfcount/
 *
 * @extends AbstractCommand<int>
 */
final class PfcountCommand extends AbstractCommand
{
    public string $id {
        get => 'PFCOUNT';
    }
}