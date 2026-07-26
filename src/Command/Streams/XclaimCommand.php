<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XCLAIM command.
 *
 * Changes the ownership of a pending message, so that the new owner is the consumer specified as the command argument.
 *
 * @see https://redis.io/commands/xclaim/
 *
 * @extends AbstractCommand<mixed>
 */
final class XclaimCommand extends AbstractCommand
{
    public string $id {
        get => 'XCLAIM';
    }
}
