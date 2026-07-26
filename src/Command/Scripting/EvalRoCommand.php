<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Scripting;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis EVAL_RO command.
 *
 * Read-only variant of the EVAL command that cannot execute commands that modify data.
 *
 * @see https://redis.io/commands/eval_ro/
 *
 * @extends AbstractCommand<mixed>
 */
final class EvalRoCommand extends AbstractCommand
{
    public string $id {
        get => 'EVAL_RO';
    }
}
