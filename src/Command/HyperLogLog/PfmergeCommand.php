<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\HyperLogLog;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis PFMERGE command.
 *
 * Merge multiple HyperLogLog values into an unique value that will approximate the cardinality of the union.
 *
 * @see https://redis.io/commands/pfmerge/
 *
 * @extends AbstractCommand<string>
 */
final class PfmergeCommand extends AbstractCommand
{
    public string $id {
        get => 'PFMERGE';
    }
}
