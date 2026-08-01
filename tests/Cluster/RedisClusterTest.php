<?php

declare(strict_types=1);

namespace Tests\Client;

use Hibla\Promise\Promise;
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

    it('shuts down cleanly using closeAsync() without leaking connections', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        await($cluster->set('shutdown_test_key', 'val'));
        expect(await($cluster->get('shutdown_test_key')))->toBe('val');

        await($cluster->closeAsync());
    });
});
