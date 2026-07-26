<?php

declare(strict_types=1);

use Hibla\EventLoop\Loop;
use Hibla\Redis\RedisClient;

use function Hibla\await;

describe('RedisClient - Sorted Sets (ZSets)', function (): void {

    it('can perform sorted set operations via ZADD, ZSCORE, ZRANGE, ZREM', function () {
        $client = createIsolatedCleanClient();

        try {
            expect(await($client->zadd('leaderboard', ['player1' => 100, 'player2' => 250])))->toBe(2);

            expect(await($client->zscore('leaderboard', 'player1')))->toBe('100')
                ->and(await($client->zscore('leaderboard', 'missing')))->toBeNull()
            ;

            expect(await($client->zrange('leaderboard', 0, -1)))->toBe(['player1', 'player2']);

            expect(await($client->zrem('leaderboard', 'player1')))->toBe(1)
                ->and(await($client->zrange('leaderboard', 0, -1)))->toBe(['player2'])
            ;
        } finally {
            $client->close();
        }
    });

    it('can perform advanced zset operations: ZINCRBY, ZCOUNT, ZRANK, ZREVRANK', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->del('adv_zset'));
            await($client->zadd('adv_zset', ['p1' => 10, 'p2' => 20, 'p3' => 30]));

            $newScore = await($client->zincrby('adv_zset', 5, 'p1'));
            expect((float) $newScore)->toBe(15.0);

            expect(await($client->zcount('adv_zset', 15, 30)))->toBe(3);
            expect(await($client->zrank('adv_zset', 'p2')))->toBe(1);
            expect(await($client->zrevrank('adv_zset', 'p2')))->toBe(1);
        } finally {
            $client->close();
        }
    });

    it('can execute BZPOPMIN and block the connection until an item arrives', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->del('my_bzpop_zset'));

            Loop::addTimer(0.1, function () {
                $pusher = new RedisClient(getConfig());
                $pusher->zadd('my_bzpop_zset', ['delayed_member' => 99])->finally(fn () => $pusher->close());
            });

            $start = microtime(true);

            $result = await($client->bzpopmin('my_bzpop_zset', 0));
            $elapsed = microtime(true) - $start;

            expect($result)->toBe(['my_bzpop_zset', 'delayed_member', '99'])
                ->and($elapsed)->toBeGreaterThanOrEqual(0.09)
            ;
        } finally {
            $client->close();
        }
    });
});