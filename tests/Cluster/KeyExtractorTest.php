<?php

declare(strict_types=1);

use Hibla\Redis\Cluster\KeyExtractor;
use Hibla\Redis\Command\AbstractCommand;
use Hibla\Redis\Command\Bitmaps\BitopCommand;
use Hibla\Redis\Command\Hashes\HgetallCommand;
use Hibla\Redis\Command\Hashes\HgetCommand;
use Hibla\Redis\Command\Json\JsonSetCommand;
use Hibla\Redis\Command\Keys\DelCommand;
use Hibla\Redis\Command\Lists\LpushCommand;
use Hibla\Redis\Command\Scripting\EvalCommand;
use Hibla\Redis\Command\Scripting\EvalRoCommand;
use Hibla\Redis\Command\Scripting\EvalshaCommand;
use Hibla\Redis\Command\Scripting\EvalshaRoCommand;
use Hibla\Redis\Command\SortedSets\ZaddCommand;
use Hibla\Redis\Command\Streams\XgroupCommand;
use Hibla\Redis\Command\Streams\XreadCommand;
use Hibla\Redis\Command\Streams\XreadgroupCommand;
use Hibla\Redis\Command\Strings\GetCommand;
use Hibla\Redis\Command\Strings\MgetCommand;
use Hibla\Redis\Command\Strings\MsetCommand;
use Hibla\Redis\Command\Strings\SetCommand;
use Hibla\Redis\Interfaces\CommandInterface;

describe('KeyExtractor', function (): void {
    it('extracts primary key from standard commands', function (CommandInterface $command, ?string $expectedKey): void {
        expect(KeyExtractor::extract($command))->toBe($expectedKey);
    })->with([
        [new GetCommand(['my_key']), 'my_key'],
        [new SetCommand(['my_key', 'val']), 'my_key'],
        [new HgetCommand(['my_hash', 'field']), 'my_hash'],
        [new HgetallCommand(['my_hash']), 'my_hash'],
        [new LpushCommand(['my_list', 'val1', 'val2']), 'my_list'],
        [new ZaddCommand(['my_zset', 10, 'member']), 'my_zset'],
        [new JsonSetCommand(['my_json', '$', '{"a":1}']), 'my_json'],
        [new DelCommand(['my_key_1', 'my_key_2']), 'my_key_1'],
    ]);

    it('extracts key from multi-key or interleaved commands', function (CommandInterface $command, ?string $expectedKey): void {
        expect(KeyExtractor::extract($command))->toBe($expectedKey);
    })->with([
        [new MsetCommand(['k1', 'v1', 'k2', 'v2']), 'k1'],
        [new MgetCommand(['k1', 'k2', 'k3']), 'k1'],
    ]);

    it('extracts key from commands where key is the second argument (BITOP, XGROUP)', function (CommandInterface $command, ?string $expectedKey): void {
        expect(KeyExtractor::extract($command))->toBe($expectedKey);
    })->with([
        [new BitopCommand(['AND', 'dest_key', 'src1', 'src2']), 'dest_key'],
        [new XgroupCommand(['CREATE', 'stream_key', 'group1', '$']), 'stream_key'],
    ]);

    it('extracts key following STREAMS token in stream read commands', function (CommandInterface $command, ?string $expectedKey): void {
        expect(KeyExtractor::extract($command))->toBe($expectedKey);
    })->with([
        [new XreadCommand(['COUNT', 10, 'BLOCK', 1000, 'STREAMS', 'my_stream', '0-0']), 'my_stream'],
        [new XreadgroupCommand(['GROUP', 'grp', 'c1', 'COUNT', 5, 'STREAMS', 'my_stream_grp', '>']), 'my_stream_grp'],
    ]);

    it('extracts key from Lua script commands (EVAL / EVALSHA / EVAL_RO / EVALSHA_RO)', function (CommandInterface $command, ?string $expectedKey): void {
        expect(KeyExtractor::extract($command))->toBe($expectedKey);
    })->with([
        [new EvalCommand(['return redis.call("GET", KEYS[1])', 1, 'eval_key', 'arg1']), 'eval_key'],
        [new EvalCommand(['return 1', 0, 'arg1']), null],
        [new EvalshaCommand(['sha1_hash', 2, 'k1', 'k2', 'arg1']), 'k1'],
        [new EvalshaRoCommand(['sha1_hash', 1, 'ro_key']), 'ro_key'],
        [new EvalRoCommand(['script', 0]), null],
    ]);

    it('extracts key when argument implements Stringable interface', function (): void {
        $stringableKey = new class () implements Stringable {
            public function __toString(): string
            {
                return 'stringable_key';
            }
        };

        $command = new GetCommand([$stringableKey]);
        expect(KeyExtractor::extract($command))->toBe('stringable_key');
    });

    it('returns null when command has no arguments', function (): void {
        $command = new class ([]) extends AbstractCommand {
            public string $id = 'PING';
        };

        expect(KeyExtractor::extract($command))->toBeNull();
    });

    it('returns null when first argument is non-scalar and non-stringable', function (): void {
        $command = new class ([['invalid_array_arg']]) extends AbstractCommand {
            public string $id = 'UNKNOWN_CMD';
        };

        expect(KeyExtractor::extract($command))->toBeNull();
    });
});
