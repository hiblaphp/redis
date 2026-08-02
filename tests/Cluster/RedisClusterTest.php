<?php

declare(strict_types=1);

namespace Tests\Client;

use Hibla\Promise\Promise;
use Hibla\Redis\Exceptions\RedisException;
use Hibla\Redis\Exceptions\TransactionException;
use Hibla\Redis\Interfaces\PipelineInterface;
use Hibla\Redis\Interfaces\RedisTransactionInterface;
use Hibla\Redis\RedisCluster;

use function Hibla\await;

beforeEach(function (): void {
    skipIfClusterNotRunning($this);
});

describe('RedisCluster Real Server Integration & Edge Cases', function (): void {
    it('discovers cluster topology and responds to keyless commands (PING, ECHO)', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            expect(await($cluster->ping()))->toBe('PONG')
                ->and(await($cluster->ping('cluster_echo')))->toBe('cluster_echo')
            ;
        } finally {
            $cluster->close();
        }
    });

    it('routes commands transparently across different master nodes', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $keysAndValues = [];
            for ($i = 0; $i < 30; $i++) {
                $keysAndValues["cluster_test_key_{$i}_" . uniqid()] = "value_{$i}";
            }

            foreach ($keysAndValues as $key => $value) {
                expect(await($cluster->set($key, $value)))->toBe('OK');
            }

            foreach ($keysAndValues as $key => $expectedValue) {
                expect(await($cluster->get($key)))->toBe($expectedValue);
            }
        } finally {
            $cluster->close();
        }
    });

    it('handles high volume concurrent commands dispatched via Promise::all across all shards', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $promises = [];
            $expectedMap = [];

            for ($i = 0; $i < 100; $i++) {
                $k = "concurrent_cluster_key_{$i}_" . uniqid();
                $v = "concurrent_val_{$i}";
                $expectedMap[$k] = $v;

                $promises[] = $cluster->set($k, $v);
            }

            await(Promise::all($promises));

            $getPromises = [];
            foreach (array_keys($expectedMap) as $k) {
                $getPromises[$k] = $cluster->get($k);
            }

            $results = await(Promise::all($getPromises));

            foreach ($expectedMap as $k => $v) {
                expect($results[$k])->toBe($v);
            }
        } finally {
            $cluster->close();
        }
    });

    it('executes multi-key commands using Hash Tags {...} on the same slot', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $tag = 'user_' . uniqid();

            await($cluster->mset([
                "{{$tag}}:name" => 'Alice',
                "{{$tag}}:email" => 'alice@example.com',
            ]));

            $values = await($cluster->mget("{{$tag}}:name", "{{$tag}}:email"));
            expect($values)->toBe(['Alice', 'alice@example.com']);
        } finally {
            $cluster->close();
        }
    });

    it('executes Lua scripts (EVAL / EVALSHA) routed to the correct cluster node', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $tag = 'script_tag_' . uniqid();
            $key = "{{$tag}}:item";
            await($cluster->set($key, '100'));

            $script = "return redis.call('INCRBY', KEYS[1], ARGV[1])";
            $res = await($cluster->eval($script, [$key], [50]));

            expect($res)->toBe(150);
            expect(await($cluster->get($key)))->toBe('150');
        } finally {
            $cluster->close();
        }
    });

    it('streams keys from all cluster master nodes concurrently via scanStream()', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $prefix = 'scan_cluster_' . uniqid() . '_';
            $keys = [];

            for ($i = 0; $i < 40; $i++) {
                $k = $prefix . $i;
                $keys[] = $k;
                await($cluster->set($k, "val_{$i}"));
            }

            $stream = await($cluster->scanStream($prefix . '*'));
            $foundKeys = [];

            foreach ($stream as $key) {
                $foundKeys[] = $key;
            }

            sort($foundKeys);
            sort($keys);

            expect($foundKeys)->toBe($keys);
        } finally {
            $cluster->close();
        }
    });

    it('operates complex data types (Hashes, Lists, Sets) across cluster nodes', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $hashKey = 'cluster_hash_' . uniqid();
            await($cluster->hset($hashKey, ['field1' => 'val1', 'field2' => 'val2']));
            expect(await($cluster->hgetall($hashKey)))->toBe(['field1' => 'val1', 'field2' => 'val2']);

            $listKey = 'cluster_list_' . uniqid();
            await($cluster->rpush($listKey, 'item1', 'item2'));
            expect(await($cluster->lrange($listKey, 0, -1)))->toBe(['item1', 'item2']);

            $setKey = 'cluster_set_' . uniqid();
            await($cluster->sadd($setKey, 'm1', 'm2'));
            expect(await($cluster->scard($setKey)))->toBe(2);
        } finally {
            $cluster->close();
        }
    });

    it('executes pipelined commands concurrently across multiple cluster nodes', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $results = await($cluster->pipeline(function (PipelineInterface $pipe) {
                $pipe->set('pipe_cluster_1', 'val1')
                    ->set('pipe_cluster_2', 'val2')
                    ->set('pipe_cluster_3', 'val3')
                    ->get('pipe_cluster_1')
                    ->get('pipe_cluster_2')
                    ->get('pipe_cluster_3')
                ;
            }));

            expect($results)->toBe([
                'OK',
                'OK',
                'OK',
                'val1',
                'val2',
                'val3',
            ]);
        } finally {
            $cluster->close();
        }
    });

    it('executes atomic MULTI/EXEC blocks when all keys map to the same hash slot', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $results = await($cluster->atomic(function (PipelineInterface $pipe) {
                $pipe->set('{groupA}:k1', 'val1')
                    ->set('{groupA}:k2', 'val2')
                    ->get('{groupA}:k1')
                    ->ping('atomic_ping')
                ;
            }));

            expect($results)->toBe([
                'OK',
                'OK',
                'val1',
                'atomic_ping',
            ]);
        } finally {
            $cluster->close();
        }
    });

    it('rejects atomic transactions immediately if keys map to different hash slots', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $promise = $cluster->atomic(function (PipelineInterface $pipe) {
                $pipe->set('cross_slot_1', 'val1')
                    ->set('cross_slot_2', 'val2')
                ;
            });

            expect(fn () => await($promise))->toThrow(
                RedisException::class,
                'Cross-slot transaction attempted'
            );
        } finally {
            $cluster->close();
        }
    });

    // --- NEW PIPELINE & ATOMIC EDGE CASE TESTS ---

    it('handles empty pipeline and empty atomic blocks gracefully without network requests', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $pipeRes = await($cluster->pipeline(function (): void {
            }));
            $atomicRes = await($cluster->atomic(function (): void {
            }));

            expect($pipeRes)->toBe([])
                ->and($atomicRes)->toBe([])
            ;
        } finally {
            $cluster->close();
        }
    });

    it('preserves strict FIFO result ordering in pipelines mixing keyless and key-routed commands across shards', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $k1 = 'fifo_key1_' . uniqid();
            $k2 = 'fifo_key2_' . uniqid();

            $results = await($cluster->pipeline(function (PipelineInterface $pipe) use ($k1, $k2) {
                $pipe->ping('p1')
                    ->set($k1, 'v1')
                    ->ping('p2')
                    ->set($k2, 'v2')
                    ->get($k1)
                    ->ping('p3')
                    ->get($k2)
                ;
            }));

            expect($results)->toBe([
                'p1',
                'OK',
                'p2',
                'OK',
                'v1',
                'p3',
                'v2',
            ]);
        } finally {
            $cluster->close();
        }
    });

    it('rejects pipeline promise if a command in the pipeline fails server-side', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $stringKey = 'pipe_wrongtype_key_' . uniqid();
            await($cluster->set($stringKey, 'string_value'));

            $promise = $cluster->pipeline(function (PipelineInterface $pipe) use ($stringKey) {
                $pipe->set('valid_key', 'val')
                    ->hgetall($stringKey)
                ;
            });

            expect(fn () => await($promise))->toThrow(
                RedisException::class,
                'WRONGTYPE'
            );
        } finally {
            $cluster->close();
        }
    });

    it('allows keyless commands (PING, ECHO) alongside same-slot key commands inside an atomic block', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $tag = 'atomic_mix_' . uniqid();

            $results = await($cluster->atomic(function (PipelineInterface $pipe) use ($tag) {
                $pipe->ping('start')
                    ->set("{{$tag}}:k1", 'v1')
                    ->ping('middle')
                    ->get("{{$tag}}:k1")
                    ->ping('end')
                ;
            }));

            expect($results)->toBe([
                'start',
                'OK',
                'middle',
                'v1',
                'end',
            ]);
        } finally {
            $cluster->close();
        }
    });

    it('handles high volume pipelining across cluster shards reliably', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $count = 300;
            $keys = [];

            $insertResults = await($cluster->pipeline(function (PipelineInterface $pipe) use ($count, &$keys) {
                for ($i = 0; $i < $count; $i++) {
                    $k = "mass_pipe_{$i}_" . uniqid();
                    $keys[] = $k;
                    $pipe->set($k, "val_{$i}");
                }
            }));

            expect($insertResults)->toHaveCount($count)
                ->and($insertResults[0])->toBe('OK')
                ->and($insertResults[$count - 1])->toBe('OK')
            ;

            $getResults = await($cluster->pipeline(function (PipelineInterface $pipe) use ($keys) {
                foreach ($keys as $k) {
                    $pipe->get($k);
                }
            }));

            expect($getResults)->toHaveCount($count)
                ->and($getResults[0])->toBe('val_0')
                ->and($getResults[$count - 1])->toBe('val_' . ($count - 1))
            ;
        } finally {
            $cluster->close();
        }
    });

    it('shuts down cleanly using closeAsync() without leaking connections', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        await($cluster->set('shutdown_test_key', 'val'));
        expect(await($cluster->get('shutdown_test_key')))->toBe('val');

        await($cluster->closeAsync());
    });

    it('automatically executes MULTI block inside transaction if exec() is omitted', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $tag = 'tx_auto_' . uniqid();

            $results = await($cluster->transaction(function (RedisTransactionInterface $tx) use ($tag) {
                await($tx->multi());
                await($tx->set("{{$tag}}:k1", 'val1'));
                await($tx->set("{{$tag}}:k2", 'val2'));
            }));

            expect($results)->toBe(['OK', 'OK'])
                ->and(await($cluster->get("{{$tag}}:k1")))->toBe('val1')
                ->and(await($cluster->get("{{$tag}}:k2")))->toBe('val2')
            ;
        } finally {
            $cluster->close();
        }
    });

    it('supports explicit exec() inside transaction block', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $tag = 'tx_explicit_' . uniqid();

            $results = await($cluster->transaction(function (RedisTransactionInterface $tx) use ($tag) {
                await($tx->multi());
                await($tx->set("{{$tag}}:k1", 'hello'));
                await($tx->get("{{$tag}}:k1"));

                return await($tx->exec());
            }));

            expect($results)->toBe(['OK', 'hello']);
        } finally {
            $cluster->close();
        }
    });

    it('allows explicit discard() to abort transaction', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $tag = 'tx_discard_' . uniqid();

            await($cluster->transaction(function (RedisTransactionInterface $tx) use ($tag) {
                await($tx->multi());
                await($tx->set("{{$tag}}:k1", 'should_not_exist'));

                await($tx->discard());
            }));

            expect(await($cluster->get("{{$tag}}:k1")))->toBeNull();
        } finally {
            $cluster->close();
        }
    });

    it('executes transaction conditionally using WATCH when key is untouched', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $key = '{watch_clean_tag}:balance_' . uniqid();
            await($cluster->set($key, '100'));

            $results = await($cluster->transaction(function (RedisTransactionInterface $tx) use ($key) {
                await($tx->watch($key));

                $val = (int) await($tx->get($key));

                await($tx->multi());
                await($tx->set($key, (string) ($val + 50)));

                return await($tx->exec());
            }));

            expect($results)->toBe(['OK'])
                ->and(await($cluster->get($key)))->toBe('150')
            ;
        } finally {
            $cluster->close();
        }
    });

    it('returns null from exec() if a watched key is modified by another client', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());
        $otherClient = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $key = '{watch_conflict_tag}:balance_' . uniqid();
            await($cluster->set($key, '100'));

            $results = await($cluster->transaction(function (RedisTransactionInterface $tx) use ($key, $otherClient) {
                await($tx->watch($key));

                // Another client modifies the key agin
                await($otherClient->set($key, '999'));

                await($tx->multi());
                await($tx->set($key, '200'));

                return await($tx->exec());
            }));

            expect($results)->toBeNull()
                ->and(await($cluster->get($key)))->toBe('999')
            ;
        } finally {
            $cluster->close();
            $otherClient->close();
        }
    });

    it('rejects cross-slot commands inside a transaction', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $promise = $cluster->transaction(function (RedisTransactionInterface $tx) {
                await($tx->set('slot_a_key', 'val'));

                await($tx->get('slot_b_key'));
            });

            expect(fn () => await($promise))->toThrow(
                TransactionException::class,
                'Cross-slot transaction attempted'
            );
        } finally {
            $cluster->close();
        }
    });

    it('rejects cross-slot keys directly inside WATCH', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $promise = $cluster->transaction(function (RedisTransactionInterface $tx) {
                await($tx->watch('slot_a_key', 'slot_b_key'));
            });

            expect(fn () => await($promise))->toThrow(
                TransactionException::class,
                'Cross-slot transaction attempted in WATCH'
            );
        } finally {
            $cluster->close();
        }
    });
});
