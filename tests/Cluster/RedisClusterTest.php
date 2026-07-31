<?php

declare(strict_types=1);

namespace Tests\Client;

use Hibla\Redis\RedisCluster;

use function Hibla\await;

beforeEach(function (): void {
    $host = getenv('REDIS_CLUSTER_HOST') !== false ? (string) getenv('REDIS_CLUSTER_HOST') : '127.0.0.1';
    $port = getenv('REDIS_CLUSTER_PORT') !== false ? (int) getenv('REDIS_CLUSTER_PORT') : 7000;

    $socket = @fsockopen($host, $port, $errno, $errstr, 0.2);
    if ($socket === false) {
        $this->markTestSkipped("Redis Cluster server is not running on {$host}:{$port}");
    } else {
        fclose($socket);
    }
});

function getClusterSeedUris(): array
{
    $host = getenv('REDIS_CLUSTER_HOST') !== false ? (string) getenv('REDIS_CLUSTER_HOST') : '127.0.0.1';
    $port = getenv('REDIS_CLUSTER_PORT') !== false ? (int) getenv('REDIS_CLUSTER_PORT') : 7000;

    return ["{$host}:{$port}"];
}

function getClusterOptions(): array
{
    $password = getenv('REDIS_CLUSTER_PASSWORD') !== false
        ? (string) getenv('REDIS_CLUSTER_PASSWORD')
        : (getenv('REDIS_PASSWORD') !== false ? (string) getenv('REDIS_PASSWORD') : 'root_password');

    return [
        'password' => $password,
    ];
}

describe('RedisCluster Real Server Integration', function (): void {
    it('discovers cluster topology and responds to PING', function (): void {
        $cluster = new RedisCluster(getClusterSeedUris(), getClusterOptions());

        try {
            $pong = await($cluster->ping());
            expect($pong)->toBe('PONG');
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
                $setResult = await($cluster->set($key, $value));
                expect($setResult)->toBe('OK');
            }

            foreach ($keysAndValues as $key => $expectedValue) {
                $actualValue = await($cluster->get($key));
                expect($actualValue)->toBe($expectedValue);
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
