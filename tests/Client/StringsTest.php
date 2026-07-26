<?php

declare(strict_types=1);

use function Hibla\await;

describe('RedisClient - Strings & Numerics', function (): void {

    it('can SET and GET string values', function () {
        $client = createIsolatedCleanClient();

        try {
            $setResult = await($client->set('my_key', 'my_value'));
            expect($setResult)->toBe('OK');

            $getResult = await($client->get('my_key'));
            expect($getResult)->toBe('my_value');

            expect(await($client->get('does_not_exist')))->toBeNull();
        } finally {
            $client->close();
        }
    });

    it('can get multiple keys via MGET', function () {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('m1', 'val1'));
            await($client->set('m2', 'val2'));

            $results = await($client->mget('m1', 'missing', 'm2'));

            expect($results)->toBe(['val1', null, 'val2']);
        } finally {
            $client->close();
        }
    });

    it('can increment and decrement numbers via INCR, DECR, INCRBY, INCRBYFLOAT', function () {
        $client = createIsolatedCleanClient();

        try {
            expect(await($client->incr('num')))->toBe(1)
                ->and(await($client->incrby('num', 5)))->toBe(6)
                ->and(await($client->decr('num')))->toBe(5)
                ->and((float) await($client->incrbyfloat('float_num', 2.5)))->toBe(2.5)
            ;
        } finally {
            $client->close();
        }
    });

    it('can set key with expiration via SETEX', function () {
        $client = createIsolatedCleanClient();

        try {
            expect(await($client->setex('setex_key', 30, 'setex_val')))->toBe('OK')
                ->and(await($client->get('setex_key')))->toBe('setex_val')
                ->and(await($client->ttl('setex_key')))->toBeGreaterThan(0)
            ;
        } finally {
            $client->close();
        }
    });

    it('can perform advanced string operations: MSET, SETNX, STRLEN, APPEND', function () {
        $client = createIsolatedCleanClient();

        try {
            $msetResult = await($client->mset([
                'multi_1' => 'val1',
                'multi_2' => 'val2',
            ]));
            expect($msetResult)->toBe('OK');
            expect(await($client->mget('multi_1', 'multi_2')))->toBe(['val1', 'val2']);

            await($client->del('nx_key'));
            expect(await($client->setnx('nx_key', 'initial')))->toBe(1);
            expect(await($client->setnx('nx_key', 'override')))->toBe(0);
            expect(await($client->get('nx_key')))->toBe('initial');

            await($client->set('append_key', 'Hello'));
            expect(await($client->append('append_key', ' World')))->toBe(11);
            expect(await($client->get('append_key')))->toBe('Hello World');
            expect(await($client->strlen('append_key')))->toBe(11);
        } finally {
            $client->close();
        }
    });
});