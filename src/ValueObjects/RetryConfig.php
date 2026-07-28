<?php

declare(strict_types=1);

namespace Hibla\Redis\ValueObjects;

final readonly class RetryConfig
{
    /**
     * @param int $maxRetries Maximum number of retry attempts (0 disables retries).
     * @param float $baseDelay Initial delay in seconds (default 0.1s / 100ms).
     * @param float $maxDelay Maximum delay cap in seconds (default 5.0s).
     * @param float $backoffFactor Multiplier for each attempt (2.0 = exponential, 1.0 = constant).
     * @param bool $jitter Whether to add randomness to the delay to prevent thundering herds.
     */
    public function __construct(
        public int $maxRetries = 3,
        public float $baseDelay = 0.1,
        public float $maxDelay = 5.0,
        public float $backoffFactor = 2.0,
        public bool $jitter = true
    ) {
    }

    /**
     * Calculates the delay in seconds for the current attempt.
     */
    public function getDelay(int $attempt): float
    {
        if ($attempt <= 0) {
            return 0.0;
        }

        $delay = $this->baseDelay * ($this->backoffFactor ** ($attempt - 1));
        $delay = min($this->maxDelay, $delay);

        if ($this->jitter && $delay > 0.0) {
            $delay = (float) mt_rand() / (float) mt_getrandmax() * $delay;
        }

        return $delay;
    }
}
