<?php

declare(strict_types=1);

use Hibla\Promise\Exceptions\CancelledException;

use function Hibla\await;
use function Hibla\delay;

describe('RedisClient - ScanStream Cancellation', function (): void {

    it('throws CancelledException and cleans up pool when stream initialization is cancelled', function (): void {
        $client = createIsolatedCleanClient(maxConnections: 1);

        try {
            await($client->set('cancel_init_1', 'val'));
            await($client->set('cancel_init_2', 'val'));

            $streamPromise = $client->scanStream('cancel_init_*');

            $streamPromise->cancel();

            expect(fn() => await($streamPromise))->toThrow(CancelledException::class);

            for ($attempt = 0; $attempt < 50; $attempt++) {
                if ($client->stats['active_connections'] === 0) {
                    break;
                }
                await(delay(0.01));
            }

            expect($client->stats['active_connections'])->toBe(0)
                ->and($client->stats['pooled_connections'])->toBe(1)
            ;

            expect(await($client->ping('Alive')))->toBe('Alive');
        } finally {
            $client->close();
        }
    });

    it('halts iteration, stops pre-fetching, and frees memory when cancelled mid-stream', function (): void {
        $client = createIsolatedCleanClient(maxConnections: 2);

        try {
            $keys = [];
            for ($i = 0; $i < 50; $i++) {
                $keys["mid_cancel_$i"] = 'val';
            }
            await($client->mset($keys));

            $stream = await($client->scanStream('mid_cancel_*', count: 10));
            $weakRef = WeakReference::create($stream);

            $processed = 0;

            foreach ($stream as $key) {
                $processed++;

                if ($processed === 5) {
                    $stream->cancel();
                }
            }

            expect($processed)->toBe(5);

            unset($stream);
            gc_collect_cycles();
            expect($weakRef->get())->toBeNull();

            for ($attempt = 0; $attempt < 50; $attempt++) {
                if ($client->stats['active_connections'] === 0) {
                    break;
                }
                await(delay(0.01));
            }

            expect($client->stats['active_connections'])->toBe(0);
            expect(await($client->ping('Clean')))->toBe('Clean');
        } finally {
            $client->close();
        }
    });

    it('allows graceful shutdown of the pool even if streams are actively pre-fetching', function (): void {
        $client = createIsolatedCleanClient(maxConnections: 2);

        try {
            $keys = [];
            for ($i = 0; $i < 500; $i++) {
                $keys["shutdown_key_$i"] = 'val';
            }
            await($client->mset($keys));

            $stream = await($client->scanStream('shutdown_key_*', count: 50));

            $processed = 0;
            $shutdownPromise = null;

            foreach ($stream as $key) {
                $processed++;

                if ($processed === 10) {
                    $shutdownPromise = $client->closeAsync();

                    $stream->cancel();
                }
            }

            expect($shutdownPromise)->not->toBeNull();

            await($shutdownPromise);
            expect($client->stats)->toBeEmpty();
        } finally {
            $client->close();
        }
    });

    it('safely abandons the stream if the underlying connection drops mid-fetch', function (): void {
        $client = createIsolatedCleanClient(maxConnections: 1);

        try {
            $keys = [];
            for ($i = 0; $i < 200; $i++) {
                $keys["drop_key_$i"] = 'val';
            }
            await($client->mset($keys));

            $stream = await($client->scanStream('drop_key_*', count: 10));

            $processed = 0;
            $caughtException = null;

            try {
                foreach ($stream as $key) {
                    $processed++;

                    if ($processed === 5) {
                        $client->close();
                    }
                }
            } catch (Throwable $e) {
                $caughtException = $e;
            }

            expect($caughtException)->not->toBeNull();
            expect($caughtException->getMessage())->toMatch('/(Connection|Client|Pool).*(clos|shutting down)/i');
        } finally {
            $client->close();
        }
    });
});
