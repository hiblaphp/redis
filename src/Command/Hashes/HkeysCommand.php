<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Hashes;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis HKEYS command.
 *
 * Returns all field names in the hash stored at key.
 *
 * @see https://redis.io/commands/hkeys/
 *
 * @extends AbstractCommand<array<int, string>>
 */
final class HkeysCommand extends AbstractCommand
{
    public string $id {
        get => 'HKEYS';
    }
}
