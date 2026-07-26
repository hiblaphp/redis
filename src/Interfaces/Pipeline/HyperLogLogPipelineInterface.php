<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface HyperLogLogPipelineInterface
{
    /**
     * Adds a PFADD command to the pipeline.
     *
     * @param string $key The key holding the HyperLogLog.
     * @param string ...$elements One or more elements to add.
     *
     * @return self For method chaining.
     */
    public function pfadd(string $key, string ...$elements): self;

    /**
     * Adds a PFCOUNT command to the pipeline.
     *
     * @param string|array<int, string> $keys One or more keys to count.
     * @param string ...$moreKeys Additional keys.
     *
     * @return self For method chaining.
     */
    public function pfcount(string|array $keys, string ...$moreKeys): self;

    /**
     * Adds a PFMERGE command to the pipeline.
     *
     * @param string $destKey The destination key.
     * @param string|array<int, string> $sourceKeys One or more source keys to merge.
     * @param string ...$moreSourceKeys Additional source keys.
     *
     * @return self For method chaining.
     */
    public function pfmerge(string $destKey, string|array $sourceKeys, string ...$moreSourceKeys): self;
}
