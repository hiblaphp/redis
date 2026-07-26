<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\HyperLogLog\PfaddCommand;
use Hibla\Redis\Command\HyperLogLog\PfcountCommand;
use Hibla\Redis\Command\HyperLogLog\PfmergeCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait HyperLogLogCommandsTrait
{
    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    abstract public function executeCommand(CommandInterface $command): PromiseInterface;

    /**
     * Adds the specified elements to the specified HyperLogLog.
     *
     * @param string $key The key holding the HyperLogLog.
     * @param string ...$elements One or more elements to add.
     *
     * @return PromiseInterface<int> Resolves to 1 if the internal register was altered, 0 otherwise.
     */
    public function pfadd(string $key, string ...$elements): PromiseInterface
    {
        return $this->executeCommand(new PfaddCommand([$key, ...$elements]));
    }

    /**
     * Returns the approximated cardinality of the specified HyperLogLog(s).
     *
     * @param string|array<int, string> $keys One or more keys to count.
     * @param string ...$moreKeys Additional keys.
     *
     * @return PromiseInterface<int> The approximated number of unique elements.
     */
    public function pfcount(string|array $keys, string ...$moreKeys): PromiseInterface
    {
        $args = \is_array($keys) ? $keys : [$keys];
        foreach ($moreKeys as $k) {
            $args[] = $k;
        }

        return $this->executeCommand(new PfcountCommand($args));
    }

    /**
     * Merge multiple HyperLogLog values into an unique value.
     *
     * @param string $destKey The destination key.
     * @param string|array<int, string> $sourceKeys One or more source keys to merge.
     * @param string ...$moreSourceKeys Additional source keys.
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function pfmerge(string $destKey, string|array $sourceKeys, string ...$moreSourceKeys): PromiseInterface
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
