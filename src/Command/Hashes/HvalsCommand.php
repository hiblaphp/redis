<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Hashes;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis HVALS command.
 *
 * Returns all values in the hash stored at key.
 *
 * @see https://redis.io/commands/hvals/
 *
 * @extends AbstractCommand<array<int, string>>
 */
final class HvalsCommand extends AbstractCommand
{
    public string $id {
        get => 'HVALS';
    }
}
