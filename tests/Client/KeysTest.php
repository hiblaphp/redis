<?php

declare(strict_types=1);

use function Hibla\await;

describe('RedisClient - Key Management', function (): void {

    it('can delete one or multiple keys via DEL and UNLINK', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('del_k1', 'v1'));
            await($client->set('del_k2', 'v2'));
            await($client->set('un_k1', 'v3'));

            $deletedCount = await($client->del('del_k1', 'del_k2', 'missing_key'));
            expect($deletedCount)->toBe(2);

            $unlinkedCount = await($client->unlink('un_k1'));
            expect($unlinkedCount)->toBe(1);

            expect(await($client->get('del_k1')))->toBeNull();
        } finally {
            $client->close();
        }
    });

    it('can check key existence via EXISTS', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('ex_1', 'v1'));
            await($client->set('ex_2', 'v2'));

            expect(await($client->exists('ex_1')))->toBe(1)
                ->and(await($client->exists('ex_1', 'ex_2', 'missing')))->toBe(2)
            ;
        } finally {
            $client->close();
        }
    });

    it('can manage key TTL and expiration via EXPIRE and TTL', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('ttl_k', 'v'));

            expect(await($client->ttl('ttl_k')))->toBe(-1);

            $expireResult = await($client->expire('ttl_k', 60));
            expect($expireResult)->toBe(1);

            $ttl = await($client->ttl('ttl_k'));
            expect($ttl)->toBeGreaterThan(0)->toBeLessThanOrEqual(60);
        } finally {
            $client->close();
        }
    });

    it('can inspect key type via TYPE', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('str_k', 'v'));
            await($client->hset('hash_k', ['f' => 'v']));

            expect(await($client->type('str_k')))->toBe('string')
                ->and(await($client->type('hash_k')))->toBe('hash')
                ->and(await($client->type('missing_k')))->toBe('none')
            ;
        } finally {
            $client->close();
        }
    });

    it('can perform advanced key operations: SCAN, RENAME, RENAMENX, PERSIST', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('old_key', 'val'));
            expect(await($client->rename('old_key', 'new_key')))->toBe('OK');
            expect(await($client->get('new_key')))->toBe('val');

            await($client->set('existing_key', 'existing'));
            expect(await($client->renamenx('new_key', 'existing_key')))->toBe(0);
            expect(await($client->renamenx('new_key', 'fresh_key')))->toBe(1);

            await($client->setex('volatile_key', 60, 'val'));
            expect(await($client->ttl('volatile_key')))->toBeGreaterThan(0);
            expect(await($client->persist('volatile_key')))->toBe(1);
            expect(await($client->ttl('volatile_key')))->toBe(-1);

            await($client->set('scan_test_1', '1'));
            await($client->set('scan_test_2', '2'));

            $cursor = '0';
            $foundKeys = [];

            do {
                [$cursor, $keys] = await($client->scan($cursor, 'scan_test_*', 10));
                $foundKeys = [...$foundKeys, ...$keys];
            } while ($cursor !== '0');

            expect($foundKeys)->toContain('scan_test_1')
                ->and($foundKeys)->toContain('scan_test_2')
            ;
        } finally {
            $client->close();
        }
    });
});
