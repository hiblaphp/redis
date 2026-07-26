<?php

declare(strict_types=1);

use function Hibla\await;

describe('RedisClient - Hashes', function (): void {

    it('can operate on hashes via HSET, HGET, HEXISTS, HMGET, HDEL, HGETALL', function () {
        $client = createIsolatedCleanClient();

        try {
            $added = await($client->hset('user:100', ['name' => 'Alice', 'role' => 'admin']));
            expect($added)->toBe(2);

            expect(await($client->hget('user:100', 'name')))->toBe('Alice')
                ->and(await($client->hexists('user:100', 'role')))->toBe(1)
                ->and(await($client->hexists('user:100', 'missing')))->toBe(0)
            ;

            expect(await($client->hmget('user:100', 'name', 'missing', 'role')))->toBe(['Alice', null, 'admin']);

            $all = await($client->hgetall('user:100'));
            expect($all)->toBe([
                'name' => 'Alice',
                'role' => 'admin',
            ]);

            expect(await($client->hdel('user:100', 'role')))->toBe(1)
                ->and(await($client->hget('user:100', 'role')))->toBeNull()
            ;
        } finally {
            $client->close();
        }
    });

    it('returns an empty array when HGETALL targets a missing key', function () {
        $client = createIsolatedCleanClient();

        try {
            $hash = await($client->hgetall('missing_hash'));
            expect($hash)->toBe([]);
        } finally {
            $client->close();
        }
    });

    it('can perform advanced hash operations: HINCRBY, HINCRBYFLOAT, HLEN, HKEYS, HVALS', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->del('adv_hash'));
            await($client->hset('adv_hash', ['clicks' => '10', 'rating' => '4.5', 'name' => 'Product A']));

            expect(await($client->hincrby('adv_hash', 'clicks', 5)))->toBe(15);
            expect((float) await($client->hincrbyfloat('adv_hash', 'rating', 0.2)))->toBe(4.7);
            expect(await($client->hlen('adv_hash')))->toBe(3);

            $keys = await($client->hkeys('adv_hash'));
            $vals = await($client->hvals('adv_hash'));

            sort($keys);

            expect($keys)->toBe(['clicks', 'name', 'rating'])
                ->and($vals)->toHaveCount(3)
                ->and($vals)->toContain('15')
                ->and($vals)->toContain('4.7')
                ->and($vals)->toContain('Product A')
            ;
        } finally {
            $client->close();
        }
    });
});