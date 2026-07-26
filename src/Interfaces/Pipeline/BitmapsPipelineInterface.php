<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface BitmapsPipelineInterface
{
    /**
     * Adds a SETBIT command to the pipeline.
     *
     * @param string $key Target key.
     * @param int $offset Bit offset.
     * @param int $value Value to set (0 or 1).
     *
     * @return self For method chaining.
     */
    public function setbit(string $key, int $offset, int $value): self;

    /**
     * Adds a GETBIT command to the pipeline.
     *
     * @param string $key Target key.
     * @param int $offset Bit offset.
     *
     * @return self For method chaining.
     */
    public function getbit(string $key, int $offset): self;

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
    public function bitcount(string $key, ?int $start = null, ?int $end = null, ?string $modifier = null): self;

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
    public function bitop(string $operation, string $destKey, string|array $sourceKeys, string ...$moreSourceKeys): self;

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
    public function bitpos(string $key, int $bit, ?int $start = null, ?int $end = null, ?string $modifier = null): self;
}
