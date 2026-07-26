<?php

declare(strict_types=1);

use Hibla\EventLoop\Loop;
use Hibla\Redis\RedisClient;

use function Hibla\await;

describe('RedisClient - Lists', function (): void {

    it('can push, pop, and inspect lists via LPUSH, RPUSH, LLEN, LPOP, RPOP', function () {
        $client = createIsolatedCleanClient();

        try {
            expect(await($client->lpush('queue', 'job2', 'job1')))->toBe(2);
            expect(await($client->rpush('queue', 'job3')))->toBe(3);

            expect(await($client->llen('queue')))->toBe(3);

            expect(await($client->lpop('queue')))->toBe('job1');
            expect(await($client->rpop('queue')))->toBe('job3');
            expect(await($client->llen('queue')))->toBe(1);
        } finally {
            $client->close();
        }
    });

    it('can perform advanced list operations: LRANGE, LTRIM, LINDEX', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->del('adv_list'));

            await($client->rpush('adv_list', 'A', 'B', 'C', 'D', 'E'));

            expect(await($client->lindex('adv_list', 0)))->toBe('A');
            expect(await($client->lindex('adv_list', -1)))->toBe('E');
            expect(await($client->lindex('adv_list', 10)))->toBeNull();

            $range = await($client->lrange('adv_list', 1, 3));
            expect($range)->toBe(['B', 'C', 'D']);

            $trimResult = await($client->ltrim('adv_list', 1, -2));
            expect($trimResult)->toBe('OK');
            expect(await($client->lrange('adv_list', 0, -1)))->toBe(['B', 'C', 'D']);
        } finally {
            $client->close();
        }
    });

    it('can execute BLPOP and block the connection until item arrives', function () {
        $client = createIsolatedCleanClient();

        try {
            Loop::addTimer(0.1, function () {
                $pusher = new RedisClient(getConfig());
                $pusher->lpush('my_list', 'popped_value')->finally(fn () => $pusher->close());
            });

            $start = microtime(true);

            $result = await($client->blpop('my_list', 0));
            $elapsed = microtime(true) - $start;

            expect($result)->toBe(['my_list', 'popped_value'])
                ->and($elapsed)->toBeGreaterThanOrEqual(0.09)
            ;
        } finally {
            $client->close();
        }
    });

    it('can execute BRPOP and block the connection until an item arrives', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->del('my_brpop_list'));

            Loop::addTimer(0.1, function () {
                $pusher = new RedisClient(getConfig());
                $pusher->lpush('my_brpop_list', 'right_popped_value')->finally(fn () => $pusher->close());
            });

            $start = microtime(true);

            $result = await($client->brpop('my_brpop_list', 0));
            $elapsed = microtime(true) - $start;

            expect($result)->toBe(['my_brpop_list', 'right_popped_value'])
                ->and($elapsed)->toBeGreaterThanOrEqual(0.09)
            ;
        } finally {
            $client->close();
        }
    });
});