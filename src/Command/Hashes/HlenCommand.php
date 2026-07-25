<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Hashes;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis HLEN command.
 *
 * Returns the number of fields contained in the hash stored at key.
 *
 * @see https://redis.io/commands/hlen/
 *
 * @extends AbstractCommand<int>
 */
final class HlenCommand extends AbstractCommand
{
    public string $id {
        get => 'HLEN';
    }
}
