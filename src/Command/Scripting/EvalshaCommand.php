<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Scripting;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis EVALSHA command.
 *
 * Evaluates a Lua script cached on the server-side by its SHA1 digest.
 *
 * @see https://redis.io/commands/evalsha/
 *
 * @extends AbstractCommand<mixed>
 */
final class EvalshaCommand extends AbstractCommand
{
    public string $id {
        get => 'EVALSHA';
    }
}
