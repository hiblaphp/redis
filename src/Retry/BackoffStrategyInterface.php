<?php

declare(strict_types=1);

namespace Hibla\Redis\Retry;

interface BackoffStrategyInterface
{
    /**
     * Calculates the delay in seconds before the next retry attempt.
     *
     * @param int $attempt The current attempt number (1-based)
     */
    public function getDelay(int $attempt): float;
}
