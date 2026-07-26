<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.ARRLEN command.
 *
 * Reports the length of the JSON array at path in key.
 *
 * @see https://redis.io/commands/json.arrlen/
 *
 * @extends AbstractCommand<array<int, int|null>|int|null>
 */
final class JsonArrlenCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.ARRLEN';
    }
}
