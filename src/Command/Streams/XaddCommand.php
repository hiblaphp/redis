<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XADD command.
 *
 * Appends the specified stream entry to the stream at key.
 *
 * @see https://redis.io/commands/xadd/
 *
 * @extends AbstractCommand<string>
 */
final class XaddCommand extends AbstractCommand
{
    public string $id {
        get => 'XADD';
    }
}
