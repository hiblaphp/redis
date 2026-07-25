<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Hashes;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis HINCRBYFLOAT command.
 *
 * Increment the specified field of a hash stored at key, and representing a floating point number, by the specified increment.
 * Resolves to the string representation of the new value to prevent precision loss.
 *
 * @see https://redis.io/commands/hincrbyfloat/
 *
 * @extends AbstractCommand<string>
 */
final class HincrbyfloatCommand extends AbstractCommand
{
    public string $id {
        get => 'HINCRBYFLOAT';
    }
}
