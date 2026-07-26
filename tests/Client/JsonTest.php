<?php

declare(strict_types=1);

use Hibla\Redis\Interfaces\PipelineInterface;
use Hibla\Redis\Interfaces\RedisTransactionInterface;
use Hibla\Redis\RedisClient;

use function Hibla\await;

describe('RedisClient - RedisJSON Commands Suite', function (): void {

    it('can store, retrieve, and delete JSON objects with path variations', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'json_test_user_' . uniqid();

            $user = [
                'name' => 'Alice',
                'age' => 30,
                'is_active' => true,
                'skills' => ['php', 'redis'],
            ];

            $setResult = await($client->jsonSet($key, '$', $user));
            expect($setResult)->toBe('OK');

            $fetched = await($client->jsonGet($key, '$'));
            expect($fetched)->toBe([$user]);

            $multiPath = await($client->jsonGet($key, '$.name', '$.age'));
            expect($multiPath)->toBeArray()
                ->and($multiPath)->toHaveKey('$.name')
                ->and($multiPath)->toHaveKey('$.age')
            ;

            $missing = await($client->jsonGet('missing_json_key_' . uniqid(), '$'));
            expect($missing)->toBeNull();

            $deletedCount = await($client->jsonDel($key, '$.skills'));
            expect($deletedCount)->toBe(1);

            $afterDel = await($client->jsonGet($key, '$.skills'));
            expect($afterDel)->toBe([]);
        } finally {
            $client->close();
        }
    });

    it('handles JSON.SET conditional flags (NX and XX)', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'json_cond_' . uniqid();

            $xxFail = await($client->jsonSet($key, '$', ['status' => 'initial'], 'XX'));
            expect($xxFail)->toBeNull();

            $nxSuccess = await($client->jsonSet($key, '$', ['status' => 'initial'], 'NX'));
            expect($nxSuccess)->toBe('OK');

            $nxFail = await($client->jsonSet($key, '$', ['status' => 'override'], 'NX'));
            expect($nxFail)->toBeNull();

            $xxSuccess = await($client->jsonSet($key, '$', ['status' => 'updated'], 'XX'));
            expect($xxSuccess)->toBe('OK');

            $final = await($client->jsonGet($key, '$.status'));
            expect($final)->toBe(['updated']);
        } finally {
            $client->close();
        }
    });

    it('can execute JSON.MGET across multiple keys including missing ones', function () {
        $client = new RedisClient(getConfig());

        try {
            $k1 = 'json_mget_1_' . uniqid();
            $k2 = 'json_mget_2_' . uniqid();
            $k3 = 'json_mget_missing_' . uniqid();

            await($client->jsonSet($k1, '$', ['role' => 'admin']));
            await($client->jsonSet($k2, '$', ['role' => 'editor']));

            $results = await($client->jsonMget([$k1, $k2, $k3], '$.role'));

            expect($results)->toBe([
                ['admin'],
                ['editor'],
                null,
            ]);
        } finally {
            $client->close();
        }
    });

    it('can manipulate numbers, arrays, booleans, and clear containers', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'json_manip_' . uniqid();

            await($client->jsonSet($key, '$', [
                'score' => 10.5,
                'enabled' => false,
                'items' => ['first'],
                'non_bool' => 'hello',
            ]));

            $newScore = await($client->jsonNumincrby($key, '$.score', 2.5));
            expect($newScore)->toBe([13.0]);

            $toggled = await($client->jsonToggle($key, '$.enabled'));
            expect($toggled)->toBe([true]);

            $toggledNonBool = await($client->jsonToggle($key, '$.non_bool'));
            expect($toggledNonBool)->toBe([null]);

            $arrLen = await($client->jsonArrappend($key, '$.items', 'second', 'third'));
            expect($arrLen)->toBe([3]);

            $typeScore = await($client->jsonType($key, '$.score'));
            $typeItems = await($client->jsonType($key, '$.items'));
            expect($typeScore)->toBe(['number'])
                ->and($typeItems)->toBe(['array'])
            ;

            $cleared = await($client->jsonClear($key, '$.items'));
            expect($cleared)->toBe(1);

            $afterClear = await($client->jsonGet($key, '$.items'));
            expect($afterClear)->toBe([[]]);
        } finally {
            $client->close();
        }
    });

    it('executes all JSON commands inside a Pipeline', function () {
        $client = new RedisClient(getConfig());

        try {
            $k1 = 'json_pipe_1_' . uniqid();
            $k2 = 'json_pipe_2_' . uniqid();

            $results = await($client->pipeline(function (PipelineInterface $pipe) use ($k1, $k2) {
                $pipe->jsonSet($k1, '$', ['counter' => 0, 'tags' => []])
                    ->jsonSet($k2, '$', ['counter' => 100])
                    ->jsonNumincrby($k1, '$.counter', 5)
                    ->jsonArrappend($k1, '$.tags', 'v1')
                    ->jsonToggle($k1, '$.counter')
                    ->jsonMget([$k1, $k2], '$.counter')
                    ->jsonType($k1, '$.tags')
                    ->jsonClear($k1, '$.tags')
                    ->jsonDel($k1, '$.counter')
                ;
            }));

            expect($results)->toHaveCount(9)
                ->and($results[0])->toBe('OK')
                ->and($results[1])->toBe('OK')
                ->and($results[2])->toBe([5])
                ->and($results[3])->toBe([1])
                ->and($results[4])->toBe([null])
                ->and($results[5])->toBe([[5], [100]])
                ->and($results[6])->toBe(['array'])
                ->and($results[7])->toBe(1)
                ->and($results[8])->toBe(1)
            ;
        } finally {
            $client->close();
        }
    });

    it('executes JSON commands inside an Atomic block', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'json_atomic_' . uniqid();

            $results = await($client->atomic(function (PipelineInterface $pipe) use ($key) {
                $pipe->jsonSet($key, '$', ['val' => 50, 'active' => true])
                    ->jsonNumincrby($key, '$.val', 25)
                    ->jsonToggle($key, '$.active')
                    ->jsonGet($key, '$')
                ;
            }));

            expect($results)->toHaveCount(4)
                ->and($results[0])->toBe('OK')
                ->and($results[1])->toBe([75])
                ->and($results[2])->toBe([false])
                ->and($results[3])->toBe([['val' => 75, 'active' => false]])
            ;
        } finally {
            $client->close();
        }
    });

    it('executes JSON commands inside an interactive Transaction with WATCH', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'json_tx_' . uniqid();
            await($client->jsonSet($key, '$', ['balance' => 500]));

            $results = await($client->transaction(function (RedisTransactionInterface $tx) use ($key) {
                await($tx->watch($key));

                $data = await($tx->jsonGet($key, '$.balance'));
                $currentBalance = $data[0] ?? 0;

                await($tx->multi());

                await($tx->jsonNumincrby($key, '$.balance', 250));

                return await($tx->exec());
            }));

            expect($results)->toBe([[750]]);

            $final = await($client->jsonGet($key, '$.balance'));
            expect($final)->toBe([750]);
        } finally {
            $client->close();
        }
    });

    it('can perform advanced JSON array and object operations', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'json_adv_' . uniqid();

            await($client->jsonSet($key, '$', [
                'list' => ['A', 'B', 'C'],
                'user' => ['name' => 'Alice', 'role' => 'admin'],
            ]));

            expect(await($client->jsonArrlen($key, '$.list')))->toBe([3]);
            $popped = await($client->jsonArrpop($key, '$.list', -1));
            expect($popped)->toBe(['C']);

            $index = await($client->jsonArrindex($key, '$.list', 'B'));
            expect($index)->toBe([1]);

            $keys = await($client->jsonObjkeys($key, '$.user'));
            $len = await($client->jsonObjlen($key, '$.user'));

            expect($keys)->toBe([['name', 'role']])
                ->and($len)->toBe([2])
            ;

        } finally {
            $client->close();
        }
    });
});
