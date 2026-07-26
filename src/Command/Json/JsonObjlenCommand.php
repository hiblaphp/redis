<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.OBJLEN command.
 *
 * Reports the number of keys in the JSON object at path.
 *
 * @see https://redis.io/commands/json.objlen/
 *
 * @extends AbstractCommand<array<int, int|null>|int|null>
 */
final class JsonObjlenCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.OBJLEN';
    }
}
