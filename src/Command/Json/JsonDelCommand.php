<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.DEL command.
 *
 * Deletes a JSON value at key and path.
 *
 * @see https://redis.io/commands/json.del/
 *
 * @extends AbstractCommand<int>
 */
final class JsonDelCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.DEL';
    }
}
