<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XTRIM command.
 *
 * Trims the stream to a given number of items.
 *
 * @see https://redis.io/commands/xtrim/
 *
 * @extends AbstractCommand<int>
 */
final class XtrimCommand extends AbstractCommand
{
    public string $id {
        get => 'XTRIM';
    }
}
