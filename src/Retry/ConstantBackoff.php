<?php

declare(strict_types=1);

namespace Hibla\Redis\Retry;

final readonly class ConstantBackoff implements BackoffStrategyInterface
{
    /**
     * @param float $delay Fixed delay in seconds (default 100ms)
     */
    public function __construct(
        private float $delay = 0.1
    ) {
    }

    public function getDelay(int $attempt): float
    {
        return $this->delay;
    }
}
