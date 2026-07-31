<?php

declare(strict_types=1);

namespace Hibla\Redis\Cluster;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\Command\AbstractCommand;
use Hibla\Redis\Exceptions\RedisException;
use Hibla\Redis\RedisClient;

final class ClusterTopology
{
    /**
     * @var array<int, string>
     */
    private array $slots = [];

    /**
     * @var PromiseInterface<void>|null
     */
    private ?PromiseInterface $discoveryPromise = null;

    /**
     * @param array<int, string> $seedUris
     * @param \Closure(string): RedisClient $clientFactory
     */
    public function __construct(
        private readonly array $seedUris,
        private readonly \Closure $clientFactory
    ) {
    }

    public function isReady(): bool
    {
        return $this->slots !== [];
    }

    public function getNodeForSlot(?int $slot): string
    {
        if ($slot !== null && isset($this->slots[$slot])) {
            return $this->slots[$slot];
        }

        if ($this->slots === []) {
            return (string) $this->seedUris[array_rand($this->seedUris)];
        }

        $uniqueNodes = array_values(array_unique($this->slots));

        return (string) $uniqueNodes[array_rand($uniqueNodes)];
    }

    public function updateSlot(int $slot, string $nodeUri): void
    {
        $this->slots[$slot] = $nodeUri;
    }

    /**
     * @return PromiseInterface<void>
     */
    public function discover(): PromiseInterface
    {
        if ($this->discoveryPromise !== null) {
            return $this->discoveryPromise;
        }

        /** @var Promise<void> $promise */
        $promise = new Promise();
        $this->discoveryPromise = $promise;

        /** @var array<int, string> $nodesToTry */
        $nodesToTry = array_values(array_unique([...$this->seedUris, ...array_values($this->slots)]));
        $this->tryDiscoverNode($nodesToTry, 0, $promise, null);

        $promise->finally(function () {
            $this->discoveryPromise = null;
        });

        return Promise::propagateCancellation($promise);
    }

    /**
     * @param array<int, string> $nodes
     * @param Promise<void> $promise
     */
    private function tryDiscoverNode(array $nodes, int $index, Promise $promise, ?\Throwable $lastException): void
    {
        if ($promise->isCancelled()) {
            return;
        }

        if (! isset($nodes[$index])) {
            $promise->reject(new RedisException('Failed to discover cluster topology', 0, $lastException));

            return;
        }

        $uri = $nodes[$index];

        /** @var RedisClient $client */
        $client = ($this->clientFactory)($uri);

        $cmd = new class ([]) extends AbstractCommand {
            public string $id = 'CLUSTER';

            public array $arguments = ['SLOTS'];
        };

        /** @var PromiseInterface<mixed> $cmdPromise */
        $cmdPromise = $client->executeCommand($cmd);

        Promise::forwardCancellation($promise, $cmdPromise);

        if ($cmdPromise !== null) {
            $cmdPromise->then(
                function (mixed $rawSlots) use ($promise, $client) {
                    if (! $promise->isCancelled()) {
                        $this->parseClusterSlots($rawSlots);
                        $promise->resolve();
                    }
                    $client->closeAsync();
                },
                function (\Throwable $e) use ($nodes, $index, $promise, $client) {
                    $client->closeAsync();
                    $this->tryDiscoverNode($nodes, $index + 1, $promise, $e);
                }
            );
        }
    }

    private function parseClusterSlots(mixed $rawSlots): void
    {
        if (! \is_array($rawSlots)) {
            return;
        }

        $newSlots = [];
        foreach ($rawSlots as $slotRange) {
            if (! \is_array($slotRange) || count($slotRange) < 3) {
                continue;
            }

            $startRaw = $slotRange[0];
            $endRaw = $slotRange[1];

            $start = \is_numeric($startRaw) ? (int) $startRaw : 0;
            $end = \is_numeric($endRaw) ? (int) $endRaw : 0;
            $masterInfo = $slotRange[2];

            if (! \is_array($masterInfo) || \count($masterInfo) < 2) {
                continue;
            }

            $ipRaw = $masterInfo[0];
            $portRaw = $masterInfo[1];

            $ip = \is_scalar($ipRaw) || $ipRaw instanceof \Stringable ? (string) $ipRaw : '';
            $port = \is_scalar($portRaw) || $portRaw instanceof \Stringable ? (string) $portRaw : '';

            if ($ip === '') {
                continue;
            }

            $uri = $ip . ':' . $port;

            for ($i = $start; $i <= $end; $i++) {
                $newSlots[$i] = $uri;
            }
        }

        $this->slots = $newSlots;
    }
}
