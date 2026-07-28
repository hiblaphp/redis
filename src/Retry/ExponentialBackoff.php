<?php

declare(strict_types=1);

namespace Hibla\Redis\Retry;

final readonly class ExponentialBackoff implements BackoffStrategyInterface
{
    /**
     * @param float $base Base delay in seconds (default 100ms)
     * @param float $maxCap Maximum delay cap in seconds (default 5s)
     * @param bool $jitter Whether to apply full jitter to prevent thundering herds
     */
    public function __construct(
        private float $base = 0.1,
        private float $maxCap = 5.0,
        private bool $jitter = true
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getDelay(int $attempt): float
    {
        $delay = min($this->maxCap, $this->base * (2 ** ($attempt - 1)));

        if ($this->jitter) {
            $delay = (float) mt_rand() / (float) mt_getrandmax() * $delay;
        }

        return $delay;
    }
}
