<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XAUTOCLAIM command.
 *
 * Automatically fetches and claims pending messages for a consumer group.
 *
 * @see https://redis.io/commands/xautoclaim/
 *
 * @extends AbstractCommand<mixed>
 */
final class XautoclaimCommand extends AbstractCommand
{
    public string $id {
        get => 'XAUTOCLAIM';
    }
}
