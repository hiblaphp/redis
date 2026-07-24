<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.CLEAR command.
 *
 * Clears container values (arrays/objects) or zeros numbers at path.
 *
 * @see https://redis.io/commands/json.clear/
 *
 * @extends AbstractCommand<int>
 */
final class JsonClearCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.CLEAR';
    }
}
