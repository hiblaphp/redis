<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Bitmaps\BitcountCommand;
use Hibla\Redis\Command\Bitmaps\BitopCommand;
use Hibla\Redis\Command\Bitmaps\BitposCommand;
use Hibla\Redis\Command\Bitmaps\GetbitCommand;
use Hibla\Redis\Command\Bitmaps\SetbitCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait BitmapsCommandsTrait
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
     * Sets or clears the bit at offset in the string value stored at key.
     *
     * @param string $key Target key.
     * @param int $offset Bit offset.
     * @param int $value Value to set (0 or 1).
     *
     * @return PromiseInterface<int> Original bit value stored at offset.
     */
    public function setbit(string $key, int $offset, int $value): PromiseInterface
    {
        return $this->executeCommand(new SetbitCommand([$key, $offset, $value]));
    }

    /**
     * Returns the bit value at offset in the string value stored at key.
     *
     * @param string $key Target key.
     * @param int $offset Bit offset.
     *
     * @return PromiseInterface<int> The bit value stored at offset (0 or 1).
     */
    public function getbit(string $key, int $offset): PromiseInterface
    {
        return $this->executeCommand(new GetbitCommand([$key, $offset]));
    }

    /**
     * Count the number of set bits (population counting) in a string.
     *
     * @param string $key Target key.
     * @param int|null $start Optional start index.
     * @param int|null $end Optional end index (inclusive).
     * @param string|null $modifier Optional 'BYTE' or 'BIT' modifier.
     *
     * @return PromiseInterface<int> Number of bits set to 1.
     */
    public function bitcount(string $key, ?int $start = null, ?int $end = null, ?string $modifier = null): PromiseInterface
    {
        $args = [$key];
        if ($start !== null && $end !== null) {
            $args[] = $start;
            $args[] = $end;
            if ($modifier !== null) {
                $args[] = strtoupper($modifier);
            }
        }

        return $this->executeCommand(new BitcountCommand($args));
    }

    /**
     * Perform a bitwise operation between multiple keys and store the result.
     *
     * @param string $operation Bitwise operation ('AND', 'OR', 'XOR', 'NOT').
     * @param string $destKey Destination key to store result.
     * @param string|array<int, string> $sourceKeys Source key(s) to apply operation to.
     * @param string ...$moreSourceKeys Additional source keys.
     *
     * @return PromiseInterface<int> The size of the string stored in the destination key.
     */
    public function bitop(string $operation, string $destKey, string|array $sourceKeys, string ...$moreSourceKeys): PromiseInterface
    {
        $args = [strtoupper($operation), $destKey];
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

        return $this->executeCommand(new BitopCommand($args));
    }

    /**
     * Return the position of the first bit set to 1 or 0 in a string.
     *
     * @param string $key Target key.
     * @param int $bit The bit to search for (0 or 1).
     * @param int|null $start Optional start index.
     * @param int|null $end Optional end index.
     * @param string|null $modifier Optional 'BYTE' or 'BIT' modifier.
     *
     * @return PromiseInterface<int> The position of the first bit found.
     */
    public function bitpos(string $key, int $bit, ?int $start = null, ?int $end = null, ?string $modifier = null): PromiseInterface
    {
        $args = [$key, $bit];
        if ($start !== null) {
            $args[] = $start;
            if ($end !== null) {
                $args[] = $end;
                if ($modifier !== null) {
                    $args[] = strtoupper($modifier);
                }
            }
        }

        return $this->executeCommand(new BitposCommand($args));
    }
}
