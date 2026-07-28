<?php

declare(strict_types=1);

namespace Hibla\Redis\Internals;

use Hibla\Promise\Interfaces\PromiseInterface;

/**
 * @internal Tracks in-flight state for command/pipeline execution retries
 * to avoid loose references in closures.
 */
final class ExecutionState
{
    public int $attempt = 0;

    /**
     * @var PromiseInterface<mixed>|null
     */
    public ?PromiseInterface $activePromise = null;

    public ?string $timerId = null;
}
