<?php

declare(strict_types=1);

use function Hibla\await;

describe('RedisClient - Bitmaps', function (): void {

    it('can manipulate and query bit strings', function () {
        $client = createIsolatedCleanClient();

        try {
            expect(await($client->setbit('bm1', 7, 1)))->toBe(0);
            expect(await($client->getbit('bm1', 7)))->toBe(1);

            await($client->setbit('bm1', 3, 1));
            expect(await($client->bitcount('bm1')))->toBe(2);

            expect(await($client->bitpos('bm1', 1)))->toBe(3);

            await($client->setbit('bm2', 7, 1));
            expect(await($client->bitop('AND', 'bm_res', 'bm1', 'bm2')))->toBe(1);

            expect(await($client->bitcount('bm_res')))->toBe(1);
        } finally {
            $client->close();
        }
    });

});
