<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Scripting;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis EVAL command.
 *
 * Evaluates a Lua script server-side.
 *
 * @see https://redis.io/commands/eval/
 *
 * @extends AbstractCommand<mixed>
 */
final class EvalCommand extends AbstractCommand
{
    public string $id {
        get => 'EVAL';
    }
}
