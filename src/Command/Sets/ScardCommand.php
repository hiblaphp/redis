<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Sets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SCARD command.
 *
 * Returns the set cardinality (number of elements) of the set stored at key.
 *
 * @see https://redis.io/commands/scard/
 *
 * @extends AbstractCommand<int>
 */
final class ScardCommand extends AbstractCommand
{
    public string $id {
        get => 'SCARD';
    }
}
