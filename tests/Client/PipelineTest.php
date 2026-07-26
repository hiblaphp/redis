<?php

declare(strict_types=1);

use Hibla\Redis\Command\AbstractCommand;
use Hibla\Redis\Exceptions\RedisException;
use Hibla\Redis\Interfaces\PipelineInterface;
use Hibla\Redis\RedisClient;

use function Hibla\await;
use function Hibla\delay;

describe('RedisClient - Explicit Pipelining', function (): void {

    it('executes an empty pipeline without error', function () {
        $client = new RedisClient(getConfig());

        try {
            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                // Do nothing
            }));

            expect($results)->toBeArray()->toBeEmpty()
                ->and($client->stats['total_connections'])->toBe(0)
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline all String and Numeric commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_str', 'p_num', 'p_float', 'p_ex', 'p_m1', 'p_m2', 'p_nx'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->set('p_str', 'val')
                     ->get('p_str')
                     ->mget('p_str', 'missing')
                     ->setex('p_ex', 10, 'exp')
                     ->incr('p_num')
                     ->decr('p_num')
                     ->incrby('p_num', 5)
                     ->incrbyfloat('p_float', 2.5)
                     ->mset(['p_m1' => 'v1', 'p_m2' => 'v2'])
                     ->setnx('p_nx', 'init')
                     ->strlen('p_str')
                     ->append('p_str', '_app')
                ;
            }));

            expect($results)->toHaveCount(12)
                ->and($results[0])->toBe('OK')
                ->and($results[1])->toBe('val')
                ->and($results[2])->toBe(['val', null])
                ->and($results[3])->toBe('OK')
                ->and($results[4])->toBe(1)
                ->and($results[5])->toBe(0)
                ->and($results[6])->toBe(5)
                ->and((float) $results[7])->toBe(2.5)
                ->and($results[8])->toBe('OK')
                ->and($results[9])->toBe(1)
                ->and($results[10])->toBe(3)
                ->and($results[11])->toBe(7)
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline all Key management commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_k1', 'p_k2', 'p_k3', 'p_k1_new', 'p_nx_new'));
            await($client->set('p_k1', 'v'));
            await($client->set('p_k2', 'v'));
            await($client->set('p_k3', 'v'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->exists('p_k1', 'p_k2')
                     ->expire('p_k1', 60)
                     ->ttl('p_k1')
                     ->type('p_k1')
                     ->del('p_k2')
                     ->unlink('p_k3')
                     ->rename('p_k1', 'p_k1_new')
                     ->renamenx('p_k1_new', 'p_nx_new')
                     ->persist('p_nx_new')
                     ->scan('0', 'p_nx_*', 10)
                ;
            }));

            expect($results)->toHaveCount(10)
                ->and($results[0])->toBe(2)
                ->and($results[1])->toBe(1)
                ->and($results[2])->toBeGreaterThan(0)->toBeLessThanOrEqual(60)
                ->and($results[3])->toBe('string')
                ->and($results[4])->toBe(1)
                ->and($results[5])->toBe(1)
                ->and($results[6])->toBe('OK')
                ->and($results[7])->toBe(1)
                ->and($results[8])->toBe(1)
                ->and($results[9])->toBeArray()->toHaveCount(2)
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline all Hash commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_hash'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->hset('p_hash', ['f1' => 'v1', 'f2' => 'v2', 'num' => '10', 'flt' => '1.5'])
                     ->hget('p_hash', 'f1')
                     ->hmget('p_hash', 'f1', 'missing')
                     ->hexists('p_hash', 'f2')
                     ->hgetall('p_hash')
                     ->hdel('p_hash', 'f1')
                     ->hincrby('p_hash', 'num', 5)
                     ->hincrbyfloat('p_hash', 'flt', 1.2)
                     ->hkeys('p_hash')
                     ->hvals('p_hash')
                     ->hlen('p_hash')
                ;
            }));

            expect($results)->toHaveCount(11)
                ->and($results[0])->toBe(4)
                ->and($results[1])->toBe('v1')
                ->and($results[2])->toBe(['v1', null])
                ->and($results[3])->toBe(1)
                ->and($results[4])->toBe(['f1' => 'v1', 'f2' => 'v2', 'num' => '10', 'flt' => '1.5'])
                ->and($results[5])->toBe(1)
                ->and($results[6])->toBe(15)
                ->and((float) $results[7])->toBe(2.7)
                ->and($results[8])->toBeArray()
                ->and($results[9])->toBeArray()
                ->and($results[10])->toBe(3)
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline all List commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_list'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->lpush('p_list', 'job2', 'job1')
                     ->rpush('p_list', 'job3', 'job4')
                     ->llen('p_list')
                     ->lpop('p_list')
                     ->rpop('p_list')
                     ->lrange('p_list', 0, -1)
                     ->lindex('p_list', 0)
                     ->ltrim('p_list', 0, 0)
                ;
            }));

            expect($results)->toHaveCount(8)
                ->and($results[0])->toBe(2) // LPUSH
                ->and($results[1])->toBe(4) // RPUSH
                ->and($results[2])->toBe(4) // LLEN
                ->and($results[3])->toBe('job1') // LPOP
                ->and($results[4])->toBe('job4') // RPOP
                ->and($results[5])->toBe(['job2', 'job3']) // LRANGE
                ->and($results[6])->toBe('job2') // LINDEX
                ->and($results[7])->toBe('OK') // LTRIM
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline all Set commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_set1', 'p_set2'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->sadd('p_set1', 'A', 'B', 'C')
                     ->sadd('p_set2', 'B', 'C', 'D')
                     ->sismember('p_set1', 'A')
                     ->smembers('p_set1')
                     ->srem('p_set1', 'C')
                     ->scard('p_set1')
                     ->sinter('p_set1', 'p_set2')
                     ->sunion('p_set1', 'p_set2')
                     ->sdiff('p_set1', 'p_set2')
                     ->spop('p_set1')
                ;
            }));

            $members = $results[3];
            sort($members);

            expect($results)->toHaveCount(10)
                ->and($results[0])->toBe(3)
                ->and($results[1])->toBe(3)
                ->and($results[2])->toBe(1)
                ->and($members)->toBe(['A', 'B', 'C'])
                ->and($results[4])->toBe(1)
                ->and($results[5])->toBe(2)
                ->and($results[6])->toBeArray()
                ->and($results[7])->toBeArray()
                ->and($results[8])->toBe(['A'])
                ->and($results[9])->toBeString()
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline all Sorted Set commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_zset'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->zadd('p_zset', ['p1' => 10, 'p2' => 20, 'p3' => 30])
                     ->zscore('p_zset', 'p1')
                     ->zrange('p_zset', 0, -1)
                     ->zrem('p_zset', 'p2')
                     ->zincrby('p_zset', 5, 'p1')
                     ->zcount('p_zset', 10, 30)
                     ->zrank('p_zset', 'p3')
                     ->zrevrank('p_zset', 'p3')
                ;
            }));

            expect($results)->toHaveCount(8)
                ->and($results[0])->toBe(3)
                ->and($results[1])->toBe('10')
                ->and($results[2])->toBe(['p1', 'p2', 'p3'])
                ->and($results[3])->toBe(1)
                ->and((float) $results[4])->toBe(15.0)
                ->and($results[5])->toBe(2)
                ->and($results[6])->toBe(1)
                ->and($results[7])->toBe(0)
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline Geo commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_geo'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->geoadd('p_geo', ['P1' => [13.361389, 38.115556], 'P2' => [15.087269, 37.502669]])
                     ->geodist('p_geo', 'P1', 'P2', 'km')
                     ->geohash('p_geo', 'P1')
                     ->geopos('p_geo', 'P1')
                     ->georadius('p_geo', 15.0, 37.5, 200, 'km')
                     ->geosearch('p_geo', 'FROMMEMBER', 'P1', 'BYRADIUS', '200', 'km')
                ;
            }));

            expect($results)->toHaveCount(6)
                ->and($results[0])->toBe(2)
                ->and((float) $results[1])->toBeGreaterThan(160)
                ->and($results[2])->toBeArray()
                ->and($results[3])->toBeArray()
                ->and($results[4])->toBeArray()
                ->and($results[5])->toBeArray()
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline JSON commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_json'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->jsonSet('p_json', '$', ['arr' => [1,2,3], 'obj' => ['k' => 'v'], 'num' => 10, 'bool' => true])
                     ->jsonGet('p_json', '$.arr')
                     ->jsonMget(['p_json'], '$.num')
                     ->jsonNumincrby('p_json', '$.num', 5)
                     ->jsonArrappend('p_json', '$.arr', 4)
                     ->jsonType('p_json', '$.arr')
                     ->jsonToggle('p_json', '$.bool')
                     ->jsonArrlen('p_json', '$.arr')
                     ->jsonArrindex('p_json', '$.arr', 2)
                     ->jsonObjkeys('p_json', '$.obj')
                     ->jsonObjlen('p_json', '$.obj')
                     ->jsonArrpop('p_json', '$.arr', -1)
                     ->jsonClear('p_json', '$.arr')
                     ->jsonDel('p_json', '$.obj')
                ;
            }));

            expect($results)->toHaveCount(14)
                ->and($results[0])->toBe('OK')
                ->and($results[1])->toBe([[1,2,3]])
                ->and($results[2])->toBe([[10]])
                ->and($results[3])->toBe([15])
                ->and($results[4])->toBe([4])
                ->and($results[5])->toBe(['array'])
                ->and($results[6])->toBe([false])
                ->and($results[7])->toBe([4])
                ->and($results[8])->toBe([1])
                ->and($results[9])->toBe([['k']])
                ->and($results[10])->toBe([1])
                ->and($results[11])->toBe([4])
                ->and($results[12])->toBe(1)
                ->and($results[13])->toBe(1)
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline Streams commands', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_strm'));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->xadd('p_strm', ['k' => 'v'], '1000-0')
                     ->xlen('p_strm')
                     ->xrange('p_strm', '-', '+')
                     ->xrevrange('p_strm', '+', '-')
                     ->xgroupCreate('p_strm', 'grp', '0', true)
                     ->xreadgroup('grp', 'c1', ['p_strm' => '>'], 1)
                     ->xread(['p_strm' => '0-0'])
                     ->xtrim('p_strm', 0, false)
                ;
            }));

            expect($results)->toHaveCount(8)
                ->and($results[0])->toBe('1000-0')
                ->and($results[1])->toBe(1)
                ->and($results[2])->toBeArray()
                ->and($results[3])->toBeArray()
                ->and($results[4])->toBe('OK')
                ->and($results[5])->toBeArray()
                ->and($results[6])->toBeArray()
                ->and($results[7])->toBe(1)
            ;
        } finally {
            $client->close();
        }
    });

    it('can safely pipeline blocking commands (BLPOP, BRPOP, BZPOPMIN, BZPOPMAX)', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->del('p_b1', 'p_b2', 'p_b3', 'p_b4'));
            await($client->lpush('p_b1', 'v1'));
            await($client->rpush('p_b2', 'v2'));
            await($client->zadd('p_b3', ['m1' => 10]));
            await($client->zadd('p_b4', ['m2' => 20]));

            $results = await($client->pipeline(function (PipelineInterface $pipe) {
                $pipe->blpop('p_b1', 1)
                     ->brpop('p_b2', 1)
                     ->bzpopmin('p_b3', 1)
                     ->bzpopmax('p_b4', 1)
                ;
            }));

            expect($results)->toHaveCount(4)
                ->and($results[0])->toBe(['p_b1', 'v1'])
                ->and($results[1])->toBe(['p_b2', 'v2'])
                ->and($results[2])->toBe(['p_b3', 'm1', '10'])
                ->and($results[3])->toBe(['p_b4', 'm2', '20'])
            ;
        } finally {
            $client->close();
        }
    });

    it('can pipeline Connection, PubSub, and Custom commands', function () {
        $client = new RedisClient(getConfig());

        try {
            $customCommand = new class (['custom_ping']) extends AbstractCommand {
                public string $id = 'PING';
            };

            $results = await($client->pipeline(function (PipelineInterface $pipe) use ($customCommand) {
                $pipe->ping('alive')
                     ->publish('p_chan', 'msg')
                     ->executeCommand($customCommand)
                ;
            }));

            expect($results)->toHaveCount(3)
                ->and($results[0])->toBe('alive')
                ->and($results[1])->toBe(0) // No subscribers in this isolated test
                ->and($results[2])->toBe('custom_ping')
            ;
        } finally {
            $client->close();
        }
    });

    it('rejects the entire pipeline promise if a command fails (Promise::all behavior)', function () {
        $client = new RedisClient(getConfig());

        try {
            await($client->set('wrong_type_key', 'string_value'));

            $promise = $client->pipeline(function (PipelineInterface $pipe) {
                $pipe->ping('first');
                $pipe->hgetall('wrong_type_key'); // This will throw WRONGTYPE
                $pipe->ping('third');
            });

            expect(fn () => await($promise))
                ->toThrow(RedisException::class, 'WRONGTYPE Operation against a key holding the wrong kind of value')
            ;

        } finally {
            $client->close();
        }
    });

    it('locks the pipeline and throws LogicException if modified after execution', function () {
        $client = new RedisClient(getConfig());

        try {
            /** @var PipelineInterface|null $leakedPipe */
            $leakedPipe = null;

            $results = await($client->pipeline(function (PipelineInterface $pipe) use (&$leakedPipe) {
                $pipe->ping('legitimate');
                $leakedPipe = $pipe;
            }));

            expect($results)->toBe(['legitimate']);

            expect(fn () => $leakedPipe->set('late_key', 'late_val'))
                ->toThrow(LogicException::class, 'Cannot add commands to a pipeline that has already been executed.')
            ;

        } finally {
            $client->close();
        }
    });

    it('can mass insert and delete reliably via pipeline', function () {
        $client = new RedisClient(getConfig());

        try {
            $insertResults = await($client->pipeline(function (PipelineInterface $pipe) {
                for ($i = 0; $i < 100; $i++) {
                    $pipe->set("mass_key_{$i}", "val_{$i}");
                }
            }));

            expect($insertResults)->toHaveCount(100)
                ->and($insertResults[0])->toBe('OK')
                ->and($insertResults[99])->toBe('OK')
            ;

            $keysToDelete = [];
            for ($i = 0; $i < 100; $i++) {
                $keysToDelete[] = "mass_key_{$i}";
            }

            $deletedCount = await($client->del(...$keysToDelete));
            expect($deletedCount)->toBe(100);

        } finally {
            $client->close();
        }
    });

    it('uses exactly one connection from the pool regardless of command count', function () {
        $client = new RedisClient(getConfig(), maxConnections: 5);

        try {
            await($client->del('p_slow_list'));

            $promise = $client->pipeline(function (PipelineInterface $pipe) {
                for ($i = 0; $i < 5; $i++) {
                    $pipe->ping("Ping {$i}");
                }

                $pipe->blpop('p_slow_list', 1);
            });

            $statsMidFlight = [];

            for ($attempt = 0; $attempt < 50; $attempt++) {
                $statsMidFlight = $client->stats;
                if ($statsMidFlight['active_connections'] === 1) {
                    break;
                }
                await(delay(0.01));
            }

            expect($statsMidFlight)->not->toBeEmpty();

            $results = await($promise);

            await(delay(0.01));

            $statsAfter = $client->stats;

            expect($results)->toHaveCount(6)
                ->and($statsMidFlight['active_connections'])->toBe(1)
                ->and($statsMidFlight['total_connections'])->toBe(1)
                ->and($statsAfter['active_connections'])->toBe(0)
                ->and($statsAfter['pooled_connections'])->toBe(1)
            ;

        } finally {
            $client->close();
        }
    });
});
