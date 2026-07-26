<?php

declare(strict_types=1);

use Hibla\Redis\Interfaces\PipelineInterface;
use Hibla\Redis\RedisClient;

use function Hibla\await;

describe('RedisClient - Lua Scripting', function (): void {

    it('can execute Lua scripts via EVAL', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->set('lua_key1', 'Hello'));
            await($client->set('lua_key2', 'World'));

            $script = "return {KEYS[1], KEYS[2], ARGV[1], ARGV[2], redis.call('GET', KEYS[1])}";

            $result = await($client->eval($script, ['lua_key1', 'lua_key2'], ['arg1', 'arg2']));

            expect($result)->toBe([
                'lua_key1',
                'lua_key2',
                'arg1',
                'arg2',
                'Hello',
            ]);
        } finally {
            $client->close();
        }
    });

    it('can load scripts, check existence, and execute via EVALSHA', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->scriptFlush('SYNC'));

            $script = 'return ARGV[1] * ARGV[2]';

            $sha1 = sha1($script);

            $existsBefore = await($client->scriptExists($sha1));
            expect($existsBefore)->toBe([0]);

            $loadedSha1 = await($client->scriptLoad($script));
            expect($loadedSha1)->toBe($sha1);

            $existsAfter = await($client->scriptExists([$sha1]));
            expect($existsAfter)->toBe([1]);

            $result = await($client->evalsha($sha1, [], [10, 5]));
            expect($result)->toBe(50);
        } finally {
            $client->close();
        }
    });

    it('can pipeline Scripting commands', function () {
        $client = new RedisClient(getConfig());

        try {
            $script1 = "return 'pipe1'";
            $script2 = "return 'pipe2'";

            $results = await($client->pipeline(function (PipelineInterface $pipe) use ($script1, $script2) {
                $pipe->scriptLoad($script1)
                     ->scriptLoad($script2)
                     ->eval($script1)
                     ->evalsha(sha1($script2))
                ;
            }));

            expect($results)->toHaveCount(4)
                ->and($results[0])->toBe(sha1($script1))
                ->and($results[1])->toBe(sha1($script2))
                ->and($results[2])->toBe('pipe1')
                ->and($results[3])->toBe('pipe2')
            ;
        } finally {
            $client->close();
        }
    });
});
