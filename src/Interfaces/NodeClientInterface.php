<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces;

use Hibla\Promise\Interfaces\PromiseInterface;

interface NodeClientInterface
{
    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    public function executeCommand(CommandInterface $command): PromiseInterface;

    /**
     * @return PromiseInterface<void>
     */
    public function closeAsync(float $timeout = 0.0): PromiseInterface;
}
