<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Lists;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis LTRIM command.
 *
 * Trim an existing list so that it will contain only the specified range of elements specified.
 *
 * @see https://redis.io/commands/ltrim/
 *
 * @extends AbstractCommand<string>
 */
final class LtrimCommand extends AbstractCommand
{
    public string $id {
        get => 'LTRIM';
    }
}
