<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.ARRAPPEND command.
 *
 * Appends JSON value(s) to the array at path.
 *
 * @see https://redis.io/commands/json.arrappend/
 *
 * @extends AbstractCommand<array<int, int|null>|int|null>
 */
final class JsonArrappendCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.ARRAPPEND';
    }
}
