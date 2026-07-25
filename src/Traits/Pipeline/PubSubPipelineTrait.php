<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\PubSub\PublishCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait PubSubPipelineTrait
{
    /**
     * @template TResponse
     *
     * @param CommandInterface<TResponse> $command
     *
     * @return self
     */
    abstract public function executeCommand(CommandInterface $command): self;

    /**
     * Adds a PUBLISH command to the pipeline.
     *
     * @param string $channel The channel to broadcast to.
     * @param string $message The message payload.
     *
     * @return self For method chaining.
     */
    public function publish(string $channel, string $message): self
    {
        return $this->executeCommand(new PublishCommand([$channel, $message]));
    }
}
