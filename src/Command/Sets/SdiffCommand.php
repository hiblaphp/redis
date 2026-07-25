<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Sets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SDIFF command.
 *
 * Returns the members of the set resulting from the difference between the first set and all the successive sets.
 *
 * @see https://redis.io/commands/sdiff/
 *
 * @extends AbstractCommand<array<int, string>>
 */
final class SdiffCommand extends AbstractCommand
{
    public string $id {
        get => 'SDIFF';
    }
}
