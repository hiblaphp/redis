<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Lists;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis BRPOP command.
 *
 * Removes and returns the last element of a list, blocking the connection
 * if the list is empty until another client pushes an element or the timeout is reached.
 *
 * @see https://redis.io/commands/brpop/
 *
 * @extends AbstractCommand<array<int, string>|null>
 */
final class BrpopCommand extends AbstractCommand
{
    public string $id {
        get => 'BRPOP';
    }

    public function isBlocking(): bool
    {
        return true;
    }
}
