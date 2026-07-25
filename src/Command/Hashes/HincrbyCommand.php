<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Hashes;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis HINCRBY command.
 *
 * Increments the number stored at field in the hash stored at key by increment.
 *
 * @see https://redis.io/commands/hincrby/
 *
 * @extends AbstractCommand<int>
 */
final class HincrbyCommand extends AbstractCommand
{
    public string $id {
        get => 'HINCRBY';
    }
}
