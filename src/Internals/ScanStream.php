<?php

declare(strict_types=1);

namespace Hibla\Redis\Internals;

use Hibla\Promise\Exceptions\CancelledException;
use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\Interfaces\ScanStreamInterface;
use SplQueue;

use function Hibla\await;

/**
 * Provides an asynchronous, pre-fetching, and backpressured stream for Redis cursor commands.
 *
 * @template TKey
 * @template TValue
 *
 * @implements ScanStreamInterface<TKey, TValue>
 */
final class ScanStream implements ScanStreamInterface
{
    /**
     * @var SplQueue<array{0: TKey|null, 1: TValue}>
     */
    private SplQueue $buffer;

    /**
     * @var Promise<array{0: TKey|null, 1: TValue}|null>|null
     */
    private ?Promise $waiter = null;

    /**
     * @var PromiseInterface<mixed>|null
     */
    private ?PromiseInterface $fetchPromise = null;

    private bool $completed = false;

    private bool $cancelled = false;

    private bool $isFetching = false;

    private ?\Throwable $error = null;

    /**
     * @param \Closure(string): PromiseInterface<array{0: string, 1: array<int, mixed>}> $fetcher Function to fetch next page.
     * @param (callable(array<int, mixed>): list<array{0: TKey|null, 1: TValue}>) $resultParser Parser mapping raw Redis array elements into [key, value] tuples.
     * @param string $cursor The current cursor position.
     * @param list<array{0: TKey|null, 1: TValue}> $initialElements The elements from the first pre-fetched page.
     * @param int $maxBufferSize Maximum buffered items before pausing background fetching.
     * @param int $fetchThreshold Buffer threshold to trigger pre-fetching the next cursor page.
     */
    public function __construct(
        private readonly \Closure $fetcher,
        private readonly mixed $resultParser,
        private string $cursor,
        array $initialElements,
        private readonly int $maxBufferSize = 1000,
        private readonly int $fetchThreshold = 200
    ) {
        /** @var SplQueue<array{0: TKey|null, 1: TValue}> $buffer */
        $buffer = new SplQueue();
        $this->buffer = $buffer;

        foreach ($initialElements as $element) {
            $this->buffer->enqueue($element);
        }

        if ($this->cursor === '0') {
            $this->completed = true;
        }
    }

    /**
      * @inheritDoc
      */
    public function getIterator(): \Generator
    {
        if (! $this->completed && $this->buffer->count() <= $this->fetchThreshold) {
            $this->ensureFetching();
        }

        while (true) {
            if ($this->error !== null) {
                if ($this->error instanceof CancelledException) {
                    break;
                }

                throw $this->error;
            }

            if (! $this->buffer->isEmpty()) {
                $bufferedItem = $this->buffer->dequeue();

                if ($this->buffer->count() <= $this->fetchThreshold) {
                    $this->ensureFetching();
                }

                if ($bufferedItem[0] !== null) {
                    yield $bufferedItem[0] => $bufferedItem[1];
                } else {
                    yield $bufferedItem[1];
                }

                continue;
            }

            if ($this->completed) {
                break;
            }

            /** @var Promise<array{0: TKey|null, 1: TValue}|null> $waiter */
            $waiter = new Promise();
            $this->waiter = $waiter;

            try {
                $awaitedItem = await($waiter);
            } catch (CancelledException) {
                break;
            } catch (\Throwable $e) {
                throw $e;
            }

            if ($awaitedItem === null) {
                break;
            }

            if ($awaitedItem[0] !== null) {
                yield $awaitedItem[0] => $awaitedItem[1];
            } else {
                yield $awaitedItem[1];
            }
        }
    }

    /**
     * Cancels the stream, aborts any in-flight background query, and clears memory.
     */
    public function cancel(): void
    {
        if ($this->cancelled) {
            return;
        }

        $this->cancelled = true;
        $this->completed = true;
        $this->error = new CancelledException('ScanStream was cancelled');

        if ($this->waiter !== null) {
            $waiter = $this->waiter;
            $this->waiter = null;
            $waiter->reject($this->error);
        }

        if ($this->fetchPromise !== null && ! $this->fetchPromise->isSettled()) {
            $this->fetchPromise->cancel();
            $this->fetchPromise = null;
        }

        /** @var SplQueue<array{0: TKey|null, 1: TValue}> $buffer */
        $buffer = new SplQueue();
        $this->buffer = $buffer;
    }

    private function ensureFetching(): void
    {
        if ($this->cancelled || $this->completed || $this->isFetching) {
            return;
        }

        // Backpressure: Pause fetching if the buffer is full
        if ($this->buffer->count() >= $this->maxBufferSize) {
            return;
        }

        if ($this->cursor === '0') {
            $this->markCompleted();

            return;
        }

        $this->isFetching = true;

        $fetcher = $this->fetcher;
        $promise = $fetcher($this->cursor);
        $this->fetchPromise = $promise;

        $promise->then(
            function (array $result): void {
                $this->fetchPromise = null;

                if ($this->cancelled) {
                    return;
                }

                $this->cursor = (string) $result[0];

                /** @var list<array{0: TKey|null, 1: TValue}> $elements */
                $elements = ($this->resultParser)($result[1]);

                foreach ($elements as $element) {
                    if ($this->waiter !== null) {
                        $waiter = $this->waiter;
                        $this->waiter = null;
                        $waiter->resolve($element);
                    } else {
                        $this->buffer->enqueue($element);
                    }
                }

                $this->isFetching = false;

                if ($this->cursor === '0') {
                    $this->markCompleted();
                } else {
                    $this->ensureFetching();
                }
            },
            function (\Throwable $e): void {
                $this->fetchPromise = null;
                $this->isFetching = false;
                $this->markError($e);
            }
        );
    }

    private function markCompleted(): void
    {
        $this->completed = true;
        if ($this->waiter !== null) {
            $waiter = $this->waiter;
            $this->waiter = null;
            $waiter->resolve(null);
        }
    }

    private function markError(\Throwable $e): void
    {
        $this->error = $e;
        $this->completed = true;
        if ($this->waiter !== null) {
            $waiter = $this->waiter;
            $this->waiter = null;
            $waiter->reject($e);
        }
    }
}
