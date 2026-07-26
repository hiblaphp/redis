<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XPENDING command.
 *
 * Inspects the list of pending messages for a consumer group.
 *
 * @see https://redis.io/commands/xpending/
 *
 * @extends AbstractCommand<mixed>
 */
final class XpendingCommand extends AbstractCommand
{
    public string $id {
        get => 'XPENDING';
    }
}
