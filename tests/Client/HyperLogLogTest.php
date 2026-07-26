<?php

declare(strict_types=1);

use function Hibla\await;

describe('RedisClient - HyperLogLog', function (): void {

    it('can count and merge approximate unique elements', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->pfadd('hll1', 'user1', 'user2', 'user3'));
            await($client->pfadd('hll2', 'user3', 'user4', 'user5'));

            expect(await($client->pfcount('hll1')))->toBe(3);
            expect(await($client->pfcount(['hll1', 'hll2'])))->toBe(5);

            expect(await($client->pfmerge('hll_merged', 'hll1', 'hll2')))->toBe('OK');
            expect(await($client->pfcount('hll_merged')))->toBe(5);
        } finally {
            $client->close();
        }
    });

});
