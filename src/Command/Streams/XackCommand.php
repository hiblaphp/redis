<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XACK command.
 *
 * Acknowledges pending stream entries for a consumer group.
 *
 * @see https://redis.io/commands/xack/
 *
 * @extends AbstractCommand<int>
 */
final class XackCommand extends AbstractCommand
{
    public string $id {
        get => 'XACK';
    }
}
