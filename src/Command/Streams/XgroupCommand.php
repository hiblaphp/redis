<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XGROUP command.
 *
 * Manages consumer groups.
 *
 * @see https://redis.io/commands/xgroup/
 *
 * @extends AbstractCommand<mixed>
 */
final class XgroupCommand extends AbstractCommand
{
    public string $id {
        get => 'XGROUP';
    }
}
