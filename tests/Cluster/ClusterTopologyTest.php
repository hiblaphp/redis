<?php

declare(strict_types=1);

use Hibla\Promise\Exceptions\CancelledException;
use Hibla\Promise\Promise;
use Hibla\Redis\Cluster\ClusterTopology;
use Hibla\Redis\Exceptions\RedisException;
use Hibla\Redis\Interfaces\NodeClientInterface;

use function Hibla\await;

describe('ClusterTopology', function (): void {
    it('starts in unready state and returns fallback seed node', function (): void {
        $topology = new ClusterTopology(
            ['127.0.0.1:7000', '127.0.0.1:7001'],
            fn (string $uri) => Mockery::mock(NodeClientInterface::class)
        );

        expect($topology->isReady())->toBeFalse();
        expect(['127.0.0.1:7000', '127.0.0.1:7001'])->toContain($topology->getNodeForSlot(100));
    });

    it('discovers cluster topology from a seed node and maps slots', function (): void {
        $clusterSlotsResponse = [
            [0, 5460, ['127.0.0.1', 7000, 'node1']],
            [5461, 10922, ['127.0.0.1', 7001, 'node2']],
            [10923, 16383, ['127.0.0.1', 7002, 'node3']],
        ];

        $clientMock = Mockery::mock(NodeClientInterface::class);
        $clientMock->shouldReceive('executeCommand')
            ->once()
            ->andReturn(Promise::resolved($clusterSlotsResponse));
        $clientMock->shouldReceive('closeAsync')
            ->once()
            ->andReturn(Promise::resolved());

        $topology = new ClusterTopology(
            ['127.0.0.1:7000'],
            fn (string $uri) => $clientMock
        );

        await($topology->discover());

        expect($topology->isReady())->toBeTrue()
            ->and($topology->getNodeForSlot(0))->toBe('127.0.0.1:7000')
            ->and($topology->getNodeForSlot(5460))->toBe('127.0.0.1:7000')
            ->and($topology->getNodeForSlot(5461))->toBe('127.0.0.1:7001')
            ->and($topology->getNodeForSlot(12000))->toBe('127.0.0.1:7002');
    });

    it('handles malformed or unexpected CLUSTER SLOTS responses gracefully', function (mixed $invalidSlotsPayload): void {
        $clientMock = Mockery::mock(NodeClientInterface::class);
        $clientMock->shouldReceive('executeCommand')
            ->once()
            ->andReturn(Promise::resolved($invalidSlotsPayload));
        $clientMock->shouldReceive('closeAsync')
            ->once()
            ->andReturn(Promise::resolved());

        $topology = new ClusterTopology(['127.0.0.1:7000'], fn () => $clientMock);

        await($topology->discover());

        expect($topology->isReady())->toBeFalse();
    })->with([
        'null payload' => null,
        'string payload' => 'OK',
        'empty array' => [[]],
        'incomplete slot range' => [[0, 100]],
        'invalid master info array' => [[0, 100, []]],
        'empty IP string' => [[0, 100, ['', 7000]]],
    ]);

    it('handles single-slot ranges where start equals end', function (): void {
        $clientMock = Mockery::mock(NodeClientInterface::class);
        $clientMock->shouldReceive('executeCommand')
            ->andReturn(Promise::resolved([
                [500, 500, ['127.0.0.1', 7000]],
            ]));
        $clientMock->shouldReceive('closeAsync')->andReturn(Promise::resolved());

        $topology = new ClusterTopology(['127.0.0.1:7000'], fn () => $clientMock);
        await($topology->discover());

        expect($topology->getNodeForSlot(500))->toBe('127.0.0.1:7000');
    });

    it('falls back to a random known mapped node when querying an unmapped slot', function (): void {
        $clientMock = Mockery::mock(NodeClientInterface::class);
        $clientMock->shouldReceive('executeCommand')->andReturn(Promise::resolved([
            [0, 5000, ['127.0.0.1', 7000]],
        ]));
        $clientMock->shouldReceive('closeAsync')->andReturn(Promise::resolved());

        $topology = new ClusterTopology(['127.0.0.1:7000'], fn () => $clientMock);
        await($topology->discover());

        expect($topology->getNodeForSlot(12000))->toBe('127.0.0.1:7000');
    });

    it('allows manual slot overrides via updateSlot', function (): void {
        $topology = new ClusterTopology(['127.0.0.1:7000'], fn () => Mockery::mock(NodeClientInterface::class));

        $topology->updateSlot(500, '127.0.0.1:7009');
        expect($topology->getNodeForSlot(500))->toBe('127.0.0.1:7009');
    });

    it('tries the next seed node if the first seed node fails', function (): void {
        $failingClient = Mockery::mock(NodeClientInterface::class);
        $failingClient->shouldReceive('executeCommand')
            ->once()
            ->andReturn(Promise::rejected(new RedisException('Connection refused')));
        $failingClient->shouldReceive('closeAsync')
            ->once()
            ->andReturn(Promise::resolved());

        $successfulClient = Mockery::mock(NodeClientInterface::class);
        $successfulClient->shouldReceive('executeCommand')
            ->once()
            ->andReturn(Promise::resolved([
                [0, 16383, ['127.0.0.1', 7001]],
            ]));
        $successfulClient->shouldReceive('closeAsync')
            ->once()
            ->andReturn(Promise::resolved());

        $topology = new ClusterTopology(
            ['127.0.0.1:7000', '127.0.0.1:7001'],
            fn (string $uri) => $uri === '127.0.0.1:7000' ? $failingClient : $successfulClient
        );

        await($topology->discover());

        expect($topology->isReady())->toBeTrue()
            ->and($topology->getNodeForSlot(100))->toBe('127.0.0.1:7001');
    });

    it('rejects with RedisException if all seed nodes fail', function (): void {
        $failingClient = Mockery::mock(NodeClientInterface::class);
        $failingClient->shouldReceive('executeCommand')
            ->andReturn(Promise::rejected(new RedisException('Connection refused')));
        $failingClient->shouldReceive('closeAsync')
            ->andReturn(Promise::resolved());

        $topology = new ClusterTopology(
            ['127.0.0.1:7000', '127.0.0.1:7001'],
            fn () => $failingClient
        );

        expect(fn () => await($topology->discover()))
            ->toThrow(RedisException::class, 'Failed to discover cluster topology');
    });

    it('prevents concurrent discovery calls (Thundering Herd protection)', function (): void {
        $clientMock = Mockery::mock(NodeClientInterface::class);
        $clientMock->shouldReceive('executeCommand')
            ->once()
            ->andReturn(Promise::resolved([
                [0, 16383, ['127.0.0.1', 7000]],
            ]));
        $clientMock->shouldReceive('closeAsync')
            ->once()
            ->andReturn(Promise::resolved());

        $topology = new ClusterTopology(
            ['127.0.0.1:7000'],
            fn () => $clientMock
        );

        $p1 = $topology->discover();
        $p2 = $topology->discover();

        expect($p1)->toBe($p2);

        await(Promise::all([$p1, $p2]));
        expect($topology->isReady())->toBeTrue();
    });

    it('cancels internal command execution when discovery promise is cancelled', function (): void {
        $cmdPromise = new Promise();

        $clientMock = Mockery::mock(NodeClientInterface::class);
        $clientMock->shouldReceive('executeCommand')
            ->once()
            ->andReturn($cmdPromise);
        $clientMock->shouldReceive('closeAsync')
            ->andReturn(Promise::resolved());

        $topology = new ClusterTopology(
            ['127.0.0.1:7000'],
            fn () => $clientMock
        );

        $discoveryPromise = $topology->discover();
        $discoveryPromise->cancel();

        expect(fn () => await($discoveryPromise))->toThrow(CancelledException::class)
            ->and($cmdPromise->isCancelled())->toBeTrue();
    });
});