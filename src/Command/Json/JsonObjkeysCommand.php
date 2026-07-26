<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.OBJKEYS command.
 *
 * Returns the keys in the object at path.
 *
 * @see https://redis.io/commands/json.objkeys/
 *
 * @extends AbstractCommand<array<int, mixed>|null>
 */
final class JsonObjkeysCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.OBJKEYS';
    }
}
