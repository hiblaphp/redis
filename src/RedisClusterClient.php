<?php

declare(strict_types=1);

namespace Hibla\Redis;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\Cluster\ClusterTopology;
use Hibla\Redis\Cluster\KeyExtractor;
use Hibla\Redis\Cluster\SlotCalculator;
use Hibla\Redis\Command\AbstractCommand;
use Hibla\Redis\Exceptions\RedisException;
use Hibla\Redis\Interfaces\CommandInterface;
use Hibla\Redis\Interfaces\RedisCommandsInterface;
use Hibla\Redis\Traits\Commands\RedisCommandsTrait;
use Hibla\Redis\ValueObjects\RedisConfig;
use Hibla\Redis\ValueObjects\RetryConfig;

final class RedisClusterClient implements RedisCommandsInterface
{
    use RedisCommandsTrait;

    private ClusterTopology $topology;

    /**
     * @var array<string, RedisClient>
     */
    private array $nodes = [];

    /**
     * @param array<int, string> $seedUris
     * @param array<string, mixed> $clientOptions
     */
    public function __construct(
        private readonly array $seedUris,
        private readonly array $clientOptions = [],
        private readonly ?RetryConfig $retryConfig = null
    ) {
        $this->topology = new ClusterTopology(
            $this->seedUris,
            $this->createNodeClient(...)
        );
    }

    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    public function executeCommand(CommandInterface $command): PromiseInterface
    {
        /** @var Promise<TReturn> $promise */
        $promise = new Promise();

        if (! $this->topology->isReady()) {
            $discovery = $this->topology->discover();

            Promise::forwardCancellation($promise, $discovery);

            if ($discovery !== null) {
                $discovery->then(
                    fn () => $this->executeWithRouting(0, $command, $promise),
                    $promise->reject(...)
                );
            }
        } else {
            $this->executeWithRouting(0, $command, $promise);
        }

        return Promise::propagateCancellation($promise);
    }

    /**
     * @return PromiseInterface<void>
     */
    public function closeAsync(float $timeout = 0.0): PromiseInterface
    {
        $promises = [];
        foreach ($this->nodes as $nodeClient) {
            $promises[] = $nodeClient->closeAsync($timeout);
        }
        
        $this->nodes = [];

        $closePromise = Promise::allSettled($promises)->then(function (): void {
        });

        return Promise::propagateCancellation($closePromise);
    }

    public function close(): void
    {
        foreach ($this->nodes as $nodeClient) {
            $nodeClient->close();
        }
        $this->nodes = [];
    }

    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     * @param Promise<TReturn> $promise
     */
    private function executeWithRouting(int $attempts, CommandInterface $command, Promise $promise): void
    {
        if ($promise->isCancelled()) {
            return;
        }

        $key = KeyExtractor::extract($command);
        $slot = $key !== null ? SlotCalculator::calculate($key) : null;
        $nodeUri = $this->topology->getNodeForSlot($slot);

        $client = $this->getNodeClient($nodeUri);

        $cmdPromise = $client->executeCommand($command);

        Promise::forwardCancellation($promise, $cmdPromise);

        if ($cmdPromise !== null) {
            $cmdPromise->then(
                $promise->resolve(...),
                function (\Throwable $e) use ($attempts, $command, $promise) {
                    if ($promise->isCancelled()) {
                        return;
                    }

                    if ($attempts >= 5) {
                        $promise->reject(new RedisException('Cluster retry limit exceeded after 5 redirections', 0, $e));

                        return;
                    }

                    $msg = $e->getMessage();

                    if (str_starts_with($msg, 'MOVED ')) {
                        $parts = explode(' ', $msg);
                        $this->topology->updateSlot((int)$parts[1], $parts[2]);
                        $this->executeWithRouting($attempts + 1, $command, $promise);

                        return;
                    }

                    if (str_starts_with($msg, 'ASK ')) {
                        $parts = explode(' ', $msg);
                        $askUri = $parts[2];
                        $this->executeAskRetry($askUri, $command, $promise, $attempts + 1);

                        return;
                    }

                    if (str_starts_with($msg, 'CLUSTERDOWN')) {
                        $discovery = $this->topology->discover();

                        Promise::forwardCancellation($promise, $discovery);

                        if ($discovery !== null) {
                            $discovery->then(
                                fn () => $this->executeWithRouting($attempts + 1, $command, $promise),
                                $promise->reject(...)
                            );
                        }

                        return;
                    }

                    $promise->reject($e);
                }
            );
        }
    }

    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     * @param Promise<TReturn> $promise
     */
    private function executeAskRetry(string $nodeUri, CommandInterface $command, Promise $promise, int $attempts): void
    {
        $client = $this->getNodeClient($nodeUri);

        $pipePromise = $client->pipeline(function ($pipe) use ($command) {
            $askingCmd = new class ([]) extends AbstractCommand {
                public string $id = 'ASKING';
            };
            $pipe->executeCommand($askingCmd);
            $pipe->executeCommand($command);
        });

        Promise::forwardCancellation($promise, $pipePromise);

        if ($pipePromise !== null) {
            $pipePromise->then(
                function (mixed $results) use ($promise) {
                    if ($promise->isCancelled()) {
                        return;
                    }

                    if (! \is_array($results)) {
                        $promise->reject(new RedisException('Unexpected pipeline response during ASK redirection'));

                        return;
                    }

                    if (($results[1] ?? null) instanceof \Throwable) {
                        $error = $results[1];
                        $promise->reject($error);
                    } else {
                        $result = $results[1] ?? null;
                        $promise->resolve($result);
                    }
                },
                $promise->reject(...)
            );
        }
    }

    private function getNodeClient(string $uri): RedisClient
    {
        if (isset($this->nodes[$uri])) {
            return $this->nodes[$uri];
        }

        $client = $this->createNodeClient($uri);
        $this->nodes[$uri] = $client;

        return $client;
    }

    private function createNodeClient(string $uri): RedisClient
    {
        $parts = explode(':', $uri);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 6379;

        $configArray = [
            ...$this->clientOptions,
            'host' => $host,
            'port' => $port,
        ];

        return new RedisClient(
            config: RedisConfig::fromArray($configArray),
            minConnections: 0,
            maxConnections: 10,
            retryConfig: $this->retryConfig
        );
    }

    public function __destruct()
    {
        $this->close();
    }
}
