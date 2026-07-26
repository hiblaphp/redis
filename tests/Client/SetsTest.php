<?php

declare(strict_types=1);

use function Hibla\await;

describe('RedisClient - Sets', function (): void {

    it('can perform set operations via SADD, SISMEMBER, SMEMBERS, SREM', function () {
        $client = createIsolatedCleanClient();

        try {
            expect(await($client->sadd('tags', 'php', 'async', 'redis', 'php')))->toBe(3);

            expect(await($client->sismember('tags', 'php')))->toBe(1)
                ->and(await($client->sismember('tags', 'python')))->toBe(0)
            ;

            $members = await($client->smembers('tags'));
            sort($members);
            expect($members)->toBe(['async', 'php', 'redis']);

            expect(await($client->srem('tags', 'async')))->toBe(1)
                ->and(await($client->sismember('tags', 'async')))->toBe(0)
            ;
        } finally {
            $client->close();
        }
    });

    it('can perform advanced set operations: SCARD, SPOP, SINTER, SUNION, SDIFF', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->sadd('set1', 'A', 'B', 'C'));
            await($client->sadd('set2', 'B', 'C', 'D'));

            expect(await($client->scard('set1')))->toBe(3);

            $inter = await($client->sinter('set1', 'set2'));
            sort($inter);
            expect($inter)->toBe(['B', 'C']);

            $union = await($client->sunion('set1', 'set2'));
            sort($union);
            expect($union)->toBe(['A', 'B', 'C', 'D']);

            $diff = await($client->sdiff('set1', 'set2'));
            expect($diff)->toBe(['A']);

            $popped = await($client->spop('set1'));
            expect($popped)->toBeString();
            expect(await($client->scard('set1')))->toBe(2);
        } finally {
            $client->close();
        }
    });
});