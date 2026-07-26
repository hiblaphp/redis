<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\HyperLogLog\PfaddCommand;
use Hibla\Redis\Command\HyperLogLog\PfcountCommand;
use Hibla\Redis\Command\HyperLogLog\PfmergeCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait HyperLogLogPipelineTrait
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
     * Adds a PFADD command to the pipeline.
     *
     * @param string $key The key holding the HyperLogLog.
     * @param string ...$elements One or more elements to add.
     *
     * @return self For method chaining.
     */
    public function pfadd(string $key, string ...$elements): self
    {
        return $this->executeCommand(new PfaddCommand([$key, ...$elements]));
    }

    /**
     * Adds a PFCOUNT command to the pipeline.
     *
     * @param string|array<int, string> $keys One or more keys to count.
     * @param string ...$moreKeys Additional keys.
     *
     * @return self For method chaining.
     */
    public function pfcount(string|array $keys, string ...$moreKeys): self
    {
        $args = \is_array($keys) ? $keys : [$keys];
        foreach ($moreKeys as $k) {
            $args[] = $k;
        }

        return $this->executeCommand(new PfcountCommand($args));
    }

    /**
     * Adds a PFMERGE command to the pipeline.
     *
     * @param string $destKey The destination key.
     * @param string|array<int, string> $sourceKeys One or more source keys to merge.
     * @param string ...$moreSourceKeys Additional source keys.
     *
     * @return self For method chaining.
     */
    public function pfmerge(string $destKey, string|array $sourceKeys, string ...$moreSourceKeys): self
    {
        $args = [$destKey];
        if (\is_array($sourceKeys)) {
            foreach ($sourceKeys as $k) {
                $args[] = $k;
            }
        } else {
            $args[] = $sourceKeys;
        }

        foreach ($moreSourceKeys as $k) {
            $args[] = $k;
        }

        return $this->executeCommand(new PfmergeCommand($args));
    }
}
