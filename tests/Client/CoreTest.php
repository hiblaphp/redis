<?php

declare(strict_types=1);

use Hibla\Promise\Promise;
use Hibla\Redis\Exceptions\ConnectionException;
use Hibla\Redis\Exceptions\PoolException;
use Hibla\Redis\RedisClient;

use function Hibla\await;

describe('RedisClient - Core Connection & Lifecycle', function (): void {

    it('lazily initializes without making connections until requested', function () {
        $client = new RedisClient(getConfig());

        try {
            expect($client->stats['total_connections'])->toBe(0);

            await($client->ping());

            expect($client->stats['total_connections'])->toBe(1);
        } finally {
            $client->close();
        }
    });

    it('can PING the server', function () {
        $client = new RedisClient(getConfig());

        try {
            $result = await($client->ping());
            expect($result)->toBe('PONG');
        } finally {
            $client->close();
        }
    });

    it('can PING the server with a custom message', function () {
        $client = new RedisClient(getConfig());

        try {
            $result = await($client->ping('Hello Redis'));
            expect($result)->toBe('Hello Redis');
        } finally {
            $client->close();
        }
    });

    it('can perform a health check on the pool', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->ping());

            $health = await($client->healthCheck());

            expect($health['total_checked'])->toBe(1)
                ->and($health['healthy'])->toBe(1)
                ->and($health['unhealthy'])->toBe(0)
            ;
        } finally {
            $client->close();
        }
    });
});

describe('RedisClient - Concurrency & Pipelines', function (): void {

    it('executes concurrent commands across multiple connections safely', function () {
        $client = createIsolatedCleanClient(maxConnections: 3);

        try {
            $promises = [
                $client->ping('A'),
                $client->ping('B'),
                $client->ping('C'),
                $client->ping('D'),
                $client->ping('E'),
            ];

            /** @var array $results */
            $results = await(Promise::all($promises));

            expect($results)->toBe(['A', 'B', 'C', 'D', 'E'])
                ->and($client->stats['total_connections'])->toBeLessThanOrEqual(3)
            ;
        } finally {
            $client->close();
        }
    });
});

describe('RedisClient - Graceful Shutdown', function (): void {

    it('closes asynchronously, waiting for pending commands to finish', function () {
        $client = new RedisClient(getConfig());

        $p1 = $client->ping('First');
        $shutdown = $client->closeAsync();

        /** @var array $results */
        $results = await(Promise::all([$p1, $shutdown]));

        expect($results[0])->toBe('First')
            ->and($client->stats)->toBeEmpty()
        ;
    });

    it('rejects commands submitted while closeAsync is pending', function () {
        $client = new RedisClient(getConfig());

        $client->closeAsync();

        expect(fn () => await($client->ping()))
            ->toThrow(PoolException::class, 'Pool is shutting down')
        ;
    });

    it('rejects commands submitted after close is called', function () {
        $client = new RedisClient(getConfig());
        $client->close();

        expect(fn () => await($client->ping()))
            ->toThrow(ConnectionException::class, 'Client is closed')
        ;
    });
});