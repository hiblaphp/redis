<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.SET command.
 *
 * Sets the JSON value at key and path.
 *
 * @see https://redis.io/commands/json.set/
 *
 * @extends AbstractCommand<string|null>
 */
final class JsonSetCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.SET';
    }
}
