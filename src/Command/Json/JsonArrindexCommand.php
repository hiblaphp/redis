<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.ARRINDEX command.
 *
 * Searches for the first occurrence of a scalar JSON value in an array.
 *
 * @see https://redis.io/commands/json.arrindex/
 *
 * @extends AbstractCommand<array<int, int|null>|int|null>
 */
final class JsonArrindexCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.ARRINDEX';
    }
}
