<?php

declare(strict_types=1);

use function Hibla\await;

describe('RedisClient - Cursor Scan Streams', function (): void {

    it('can stream keys across multiple pages using scanStream', function (): void {
        $client = createIsolatedCleanClient();

        try {
            $keys = [];
            for ($i = 0; $i < 50; $i++) {
                $keys["scan_key_$i"] = "val_$i";
            }
            await($client->mset($keys));

            $stream = await($client->scanStream('scan_key_*', count: 10));
            $foundKeys = [];

            foreach ($stream as $key) {
                $foundKeys[] = $key;
            }

            sort($foundKeys);
            $expected = array_keys($keys);
            sort($expected);

            expect($foundKeys)->toBe($expected);
        } finally {
            $client->close();
        }
    });

    it('can stream hash fields and values using hscanStream', function (): void {
        $client = createIsolatedCleanClient();

        try {
            $hashKey = 'hscan_test_hash';
            $fields = [];
            for ($i = 0; $i < 30; $i++) {
                $fields["field_$i"] = "value_$i";
            }
            await($client->hset($hashKey, $fields));

            $stream = await($client->hscanStream($hashKey, count: 5));
            $foundFields = [];

            foreach ($stream as $field => $value) {
                $foundFields[$field] = $value;
            }

            ksort($foundFields);
            ksort($fields);

            expect($foundFields)->toBe($fields);
        } finally {
            $client->close();
        }
    });

    it('can stream set members using sscanStream', function (): void {
        $client = createIsolatedCleanClient();

        try {
            $setKey = 'sscan_test_set';
            $members = [];
            for ($i = 0; $i < 30; $i++) {
                $members[] = "member_$i";
            }
            await($client->sadd($setKey, ...$members));

            $stream = await($client->sscanStream($setKey, count: 5));
            $foundMembers = [];

            foreach ($stream as $member) {
                $foundMembers[] = $member;
            }

            sort($foundMembers);
            sort($members);

            expect($foundMembers)->toBe($members);
        } finally {
            $client->close();
        }
    });

    it('can stream sorted set members and scores using zscanStream', function (): void {
        $client = createIsolatedCleanClient();

        try {
            $zsetKey = 'zscan_test_zset';
            $members = [];
            for ($i = 0; $i < 30; $i++) {
                $members["zmember_$i"] = (float) $i;
            }
            await($client->zadd($zsetKey, $members));

            $stream = await($client->zscanStream($zsetKey, count: 5));
            $foundMembers = [];

            foreach ($stream as $member => $score) {
                $foundMembers[$member] = $score;
            }

            ksort($foundMembers);
            ksort($members);

            expect($foundMembers)->toBe($members);
        } finally {
            $client->close();
        }
    });

    it('handles pattern matching in stream commands', function (): void {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('match_apple', '1'));
            await($client->set('match_banana', '2'));
            await($client->set('other_orange', '3'));

            $stream = await($client->scanStream('match_*'));
            $found = [];

            foreach ($stream as $key) {
                $found[] = $key;
            }

            sort($found);

            expect($found)->toBe(['match_apple', 'match_banana']);
        } finally {
            $client->close();
        }
    });

    it('handles scanning empty or missing keys gracefully', function (): void {
        $client = createIsolatedCleanClient();

        try {
            $stream = await($client->scanStream('non_existing_pattern_*'));
            $found = [];

            foreach ($stream as $key) {
                $found[] = $key;
            }

            expect($found)->toBeEmpty();
        } finally {
            $client->close();
        }
    });

    it('maintains low and flat memory usage when streaming thousands of items', function (): void {
        $client = createIsolatedCleanClient();

        try {
            $batch = [];
            for ($i = 0; $i < 2000; $i++) {
                $batch["mem_key_$i"] = "val_$i";
                if (count($batch) === 200) {
                    await($client->mset($batch));
                    $batch = [];
                }
            }
            if ($batch !== []) {
                await($client->mset($batch));
            }

            gc_collect_cycles();
            $initialMemory = memory_get_usage();

            $stream = await($client->scanStream('mem_key_*', count: 20));

            $processedCount = 0;
            $midMemory = 0;

            foreach ($stream as $key) {
                $processedCount++;

                if ($processedCount === 1000) {
                    $midMemory = memory_get_usage();
                }
            }

            gc_collect_cycles();
            $finalMemory = memory_get_usage();

            expect($processedCount)->toBe(2000);

            $growth = $midMemory - $initialMemory;
            expect($growth)->toBeLessThan(500 * 1024);

            $finalDelta = abs($finalMemory - $initialMemory);
            expect($finalDelta)->toBeLessThan(100 * 1024);
        } finally {
            $client->close();
        }
    });

    it('garbage collects ScanStream cleanly without circular reference memory leaks', function (): void {
        $client = createIsolatedCleanClient();

        try {
            await($client->set('gc_key_1', 'val1'));
            await($client->set('gc_key_2', 'val2'));

            $stream = await($client->scanStream('gc_key_*'));
            $weakRef = WeakReference::create($stream);

            foreach ($stream as $key) {
                // Consume stream
            }

            unset($stream);
            gc_collect_cycles();

            expect($weakRef->get())->toBeNull();
        } finally {
            $client->close();
        }
    });

});
