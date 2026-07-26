<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Bitmaps\BitcountCommand;
use Hibla\Redis\Command\Bitmaps\BitopCommand;
use Hibla\Redis\Command\Bitmaps\BitposCommand;
use Hibla\Redis\Command\Bitmaps\GetbitCommand;
use Hibla\Redis\Command\Bitmaps\SetbitCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait BitmapsPipelineTrait
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
     * Adds a SETBIT command to the pipeline.
     *
     * @param string $key Target key.
     * @param int $offset Bit offset.
     * @param int $value Value to set (0 or 1).
     *
     * @return self For method chaining.
     */
    public function setbit(string $key, int $offset, int $value): self
    {
        return $this->executeCommand(new SetbitCommand([$key, $offset, $value]));
    }

    /**
     * Adds a GETBIT command to the pipeline.
     *
     * @param string $key Target key.
     * @param int $offset Bit offset.
     *
     * @return self For method chaining.
     */
    public function getbit(string $key, int $offset): self
    {
        return $this->executeCommand(new GetbitCommand([$key, $offset]));
    }

    /**
     * Adds a BITCOUNT command to the pipeline.
     *
     * @param string $key Target key.
     * @param int|null $start Optional start index.
     * @param int|null $end Optional end index (inclusive).
     * @param string|null $modifier Optional 'BYTE' or 'BIT' modifier.
     *
     * @return self For method chaining.
     */
    public function bitcount(string $key, ?int $start = null, ?int $end = null, ?string $modifier = null): self
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
     * Adds a BITOP command to the pipeline.
     *
     * @param string $operation Bitwise operation ('AND', 'OR', 'XOR', 'NOT').
     * @param string $destKey Destination key to store result.
     * @param string|array<int, string> $sourceKeys Source key(s) to apply operation to.
     * @param string ...$moreSourceKeys Additional source keys.
     *
     * @return self For method chaining.
     */
    public function bitop(string $operation, string $destKey, string|array $sourceKeys, string ...$moreSourceKeys): self
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
     * Adds a BITPOS command to the pipeline.
     *
     * @param string $key Target key.
     * @param int $bit The bit to search for (0 or 1).
     * @param int|null $start Optional start index.
     * @param int|null $end Optional end index.
     * @param string|null $modifier Optional 'BYTE' or 'BIT' modifier.
     *
     * @return self For method chaining.
     */
    public function bitpos(string $key, int $bit, ?int $start = null, ?int $end = null, ?string $modifier = null): self
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
