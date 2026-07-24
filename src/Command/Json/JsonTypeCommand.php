<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.TYPE command.
 *
 * Reports the type of JSON value at path.
 *
 * @see https://redis.io/commands/json.type/
 *
 * @extends AbstractCommand<array<int, string|null>|string|null>
 */
final class JsonTypeCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.TYPE';
    }
}
