<?php

declare(strict_types=1);

namespace Tests\Cancellation;

use Hibla\Promise\Exceptions\CancelledException;
use Hibla\Promise\Interfaces\SettledResultInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\RedisCluster;

use function Hibla\await;
use function Hibla\delay;

beforeEach(function (): void {
    skipIfClusterNotRunning($this);
});

describe('RedisCluster Cancellation', function (): void {

    it('cancels pending commands cleanly while cluster topology is being discovered', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $promise1 = $cluster->get('cancel_discovery_key1');
            $promise2 = $cluster->get('cancel_discovery_key2');

            $promise1->cancel();
            $promise2->cancel();

            expect(fn () => await($promise1))->toThrow(CancelledException::class);
            expect(fn () => await($promise2))->toThrow(CancelledException::class);

            await(delay(0.1));

            expect(await($cluster->ping('alive')))->toBe('alive');
        } finally {
            $cluster->close();
        }
    });

    it('cancels a command mid-flight and does not hang the event loop', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            await($cluster->ping());

            $blpopPromise = $cluster->blpop('cluster_cancel_list', 10);

            await(delay(0.05));
            $blpopPromise->cancel();

            expect(fn () => await($blpopPromise))->toThrow(CancelledException::class);

            expect(await($cluster->set('cluster_cancel_recovery', 'ok')))->toBe('OK');
        } finally {
            $cluster->close();
        }
    });

    it('propagates cancellation to all master nodes during a ClusterScanStream', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $prefix = 'cluster_cancel_scan_';
            $keys = [];
            for ($i = 0; $i < 50; $i++) {
                $k = $prefix . $i;
                $keys[] = $k;
                await($cluster->set($k, "val_{$i}"));
            }

            $stream = await($cluster->scanStream($prefix . '*'));

            $processed = 0;
            foreach ($stream as $key) {
                $processed++;
                if ($processed === 5) {
                    $stream->cancel();
                }
            }

            expect($processed)->toBe(5);

            expect(await($cluster->ping('operational')))->toBe('operational');
        } finally {
            $cluster->close();
        }
    });

    it('aborts graceful shutdown cleanly if shutdown promise is cancelled', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        await($cluster->ping());

        $blockPromise = $cluster->blpop('cluster_shutdown_block', 0);
        $shutdownPromise = $cluster->closeAsync();
        $shutdownPromise->cancel();

        expect(fn () => await($shutdownPromise))->toThrow(CancelledException::class);

        $blockPromise->cancel();

        try {
            await($blockPromise);
        } catch (\Throwable) {
            // expected
        }

        $cluster->close();
    });

    it('handles high volume concurrent cancellations securely', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            await($cluster->ping());

            /** @var array<int, \Hibla\Promise\Interfaces\PromiseInterface<mixed>> $promises */
            $promises = [];

            for ($i = 0; $i < 50; $i++) {
                $promises[] = $cluster->blpop("cluster_mass_cancel_{$i}_" . uniqid(), 50);
            }

            await(delay(0.05));

            foreach ($promises as $promise) {
                $promise->cancel();
            }

            /** @var array<int, SettledResultInterface<mixed, \Throwable>> $results */
            $results = await(Promise::allSettled($promises));

            foreach ($results as $result) {
                expect($result->isCancelled())->toBeTrue();
            }

            expect(await($cluster->set('mass_cancel_recovery', 'yes')))->toBe('OK');
        } finally {
            $cluster->close();
        }
    });

    it('cancels a command immediately (zero-tick) before it hits the network', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            await($cluster->ping());

            $key = 'immediate_cancel_key_' . uniqid();

            $promise = $cluster->set($key, 'should_not_exist');
            $promise->cancel();

            expect(fn () => await($promise))->toThrow(CancelledException::class);

            expect(await($cluster->get($key)))->toBeNull();
        } finally {
            $cluster->close();
        }
    });

    it('cancels a scanStream initialization cleanly before discovery completes', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $streamPromise = $cluster->scanStream('pre_init_scan_*');

            $streamPromise->cancel();

            expect(fn () => await($streamPromise))->toThrow(CancelledException::class);

            expect(await($cluster->ping('alive')))->toBe('alive');
        } finally {
            $cluster->close();
        }
    });

    it('unblocks closeAsync() immediately when active blocking commands are cancelled', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            await($cluster->ping());
            $blockPromise = $cluster->blpop('wait_for_close_key', 50);

            $closePromise = $cluster->closeAsync();

            await(delay(0.05));
            expect($closePromise->isPending())->toBeTrue();

            $blockPromise->cancel();

            expect(fn () => await($blockPromise))->toThrow(CancelledException::class);
            await($closePromise);

            expect($closePromise->isFulfilled())->toBeTrue();
        } finally {
            $cluster->close();
        }
    });

    it('safely protects shared discovery from being cancelled by an individual command (Thundering Herd Protection)', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $p1 = $cluster->get('shared_k1');
            $p2 = $cluster->get('shared_k2');
            $p3 = $cluster->get('shared_k3');

            $p1->cancel();

            /** @var array<int, SettledResultInterface<mixed, \Throwable>> $results */
            $results = await(Promise::allSettled([$p1, $p2, $p3]));

            expect($results[0]->isCancelled())->toBeTrue();
            expect($results[1]->isFulfilled())->toBeTrue();
            expect($results[2]->isFulfilled())->toBeTrue();
        } finally {
            $cluster->close();
        }
    });
});
