<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Scripting;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis EVALSHA_RO command.
 *
 * Read-only variant of the EVALSHA command that cannot execute commands that modify data.
 *
 * @see https://redis.io/commands/evalsha_ro/
 *
 * @extends AbstractCommand<mixed>
 */
final class EvalshaRoCommand extends AbstractCommand
{
    public string $id {
        get => 'EVALSHA_RO';
    }
}
