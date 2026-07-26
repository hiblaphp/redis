<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Scripting;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SCRIPT command.
 *
 * Container command for SCRIPT LOAD, SCRIPT EXISTS, SCRIPT FLUSH, SCRIPT KILL, and SCRIPT DEBUG.
 *
 * @see https://redis.io/commands/script/
 *
 * @extends AbstractCommand<mixed>
 */
final class ScriptCommand extends AbstractCommand
{
    public string $id {
        get => 'SCRIPT';
    }
}
