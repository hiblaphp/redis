<?php

declare(strict_types=1);

use Hibla\Redis\Interfaces\PipelineInterface;
use Hibla\Redis\RedisClient;

use function Hibla\await;

describe('RedisClient - Streams Commands', function (): void {

    it('can add, count, range, trim, and delete entries in a stream', function () {
        $client = new RedisClient(getConfig());

        try {
            $stream = 'test_stream_' . uniqid();

            $id1 = await($client->xadd($stream, ['sensor' => 'temp', 'value' => '22.5']));
            $id2 = await($client->xadd($stream, ['sensor' => 'temp', 'value' => '23.0']));

            expect($id1)->toBeString()
                ->and($id2)->toBeString()
            ;

            expect(await($client->xlen($stream)))->toBe(2);

            $range = await($client->xrange($stream, '-', '+'));
            expect($range)->toHaveKey($id1)
                ->and($range[$id1])->toBe(['sensor' => 'temp', 'value' => '22.5'])
            ;

            $revRange = await($client->xrevrange($stream, '+', '-'));
            expect(array_keys($revRange)[0])->toBe($id2);

            $readData = await($client->xread([$stream => '0-0']));
            expect($readData)->toHaveKey($stream)
                ->and($readData[$stream])->toHaveKey($id1)
            ;

            $deleted = await($client->xdel($stream, $id1));
            expect($deleted)->toBe(1);

            expect(await($client->xlen($stream)))->toBe(1);
        } finally {
            $client->close();
        }
    });

    it('can create consumer groups and read via XREADGROUP', function () {
        $client = new RedisClient(getConfig());

        try {
            $stream = 'cg_stream_' . uniqid();
            $group = 'cg_group_' . uniqid();

            await($client->xadd($stream, ['action' => 'login', 'user' => '101']));

            await($client->xgroupCreate($stream, $group, '0', true));

            $read = await($client->xreadgroup($group, 'consumer1', [$stream => '>'], count: 1));

            expect($read)->toHaveKey($stream);
            $entries = $read[$stream];
            $entryId = array_key_first($entries);

            expect($entries[$entryId])->toBe(['action' => 'login', 'user' => '101']);

            $ack = await($client->xack($stream, $group, $entryId));
            expect($ack)->toBe(1);
        } finally {
            $client->close();
        }
    });

    it('can pipeline stream commands', function () {
        $client = new RedisClient(getConfig());

        try {
            $stream = 'pipe_stream_' . uniqid();

            $results = await($client->pipeline(function (PipelineInterface $pipe) use ($stream) {
                $pipe->xadd($stream, ['step' => '1'], '1000-0')
                    ->xadd($stream, ['step' => '2'], '2000-0')
                    ->xlen($stream)
                    ->xrange($stream)
                ;
            }));

            expect($results)->toHaveCount(4)
                ->and($results[0])->toBe('1000-0')
                ->and($results[1])->toBe('2000-0')
                ->and($results[2])->toBe(2)
                ->and($results[3])->toHaveKey('1000-0')
            ;
        } finally {
            $client->close();
        }
    });

    it('can inspect and claim pending messages via XPENDING, XCLAIM, and XAUTOCLAIM', function () {
        $client = new RedisClient(getConfig());

        try {
            $stream = 'stream_reliability_' . uniqid();
            $group = 'group_workers_' . uniqid();

            await($client->xgroupCreate($stream, $group, '0', true));
            $id1 = await($client->xadd($stream, ['job' => 'task_1']));
            $id2 = await($client->xadd($stream, ['job' => 'task_2']));

            await($client->xreadgroup($group, 'consumer_1', [$stream => '>'], count: 2));

            $pendingSummary = await($client->xpending($stream, $group));
            expect($pendingSummary[0])->toBe(2)
                ->and($pendingSummary[1])->toBe($id1)
                ->and($pendingSummary[2])->toBe($id2)
            ;

            $claimed = await($client->xclaim($stream, $group, 'consumer_2', 0, [$id1]));
            expect($claimed)->toBeArray()->not->toBeEmpty();

            $claimedId = $claimed[0][0] ?? null;
            expect($claimedId)->toBe($id1);

            await($client->xack($stream, $group, $id1));

            $autoClaimed = await($client->xautoclaim($stream, $group, 'consumer_2', 0, '0-0'));

            expect($autoClaimed)->toBeArray();
            expect(count($autoClaimed))->toBeGreaterThanOrEqual(2);

            $messages = $autoClaimed[1];
            expect($messages)->toBeArray()->not->toBeEmpty();

            $autoClaimedId = $messages[0][0] ?? null;
            expect($autoClaimedId)->toBe($id2);

        } finally {
            $client->close();
        }
    });
});
