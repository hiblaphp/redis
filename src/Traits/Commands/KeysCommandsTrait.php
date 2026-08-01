<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\Command\Keys\DelCommand;
use Hibla\Redis\Command\Keys\ExistsCommand;
use Hibla\Redis\Command\Keys\ExpireCommand;
use Hibla\Redis\Command\Keys\PersistCommand;
use Hibla\Redis\Command\Keys\RenameCommand;
use Hibla\Redis\Command\Keys\RenamenxCommand;
use Hibla\Redis\Command\Keys\ScanCommand;
use Hibla\Redis\Command\Keys\TtlCommand;
use Hibla\Redis\Command\Keys\TypeCommand;
use Hibla\Redis\Command\Keys\UnlinkCommand;
use Hibla\Redis\Interfaces\CommandInterface;
use Hibla\Redis\Interfaces\ScanStreamInterface;
use Hibla\Redis\Internals\ScanStream;

trait KeysCommandsTrait
{
    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    abstract public function executeCommand(CommandInterface $command): PromiseInterface;

    /**
     * Removes the specified keys. A key is ignored if it does not exist.
     *
     * @param string ...$keys One or more keys to delete.
     *
     * @return PromiseInterface<int> Resolves to the number of keys removed.
     */
    public function del(string ...$keys): PromiseInterface
    {
        return $this->executeCommand(new DelCommand($keys));
    }

    /**
     * Returns the number of keys that exist among the requested keys.
     *
     * @param string ...$keys One or more keys to check.
     *
     * @return PromiseInterface<int> Resolves to the count of existing keys.
     */
    public function exists(string ...$keys): PromiseInterface
    {
        return $this->executeCommand(new ExistsCommand($keys));
    }

    /**
     * Sets a timeout on key in seconds.
     *
     * @param string $key Target key.
     * @param int $seconds Timeout in seconds.
     *
     * @return PromiseInterface<int> Resolves to 1 if timeout was set, 0 if key missing.
     */
    public function expire(string $key, int $seconds): PromiseInterface
    {
        return $this->executeCommand(new ExpireCommand([$key, $seconds]));
    }

    /**
     * Returns the remaining time to live of a key in seconds.
     *
     * @param string $key Key to inspect.
     *
     * @return PromiseInterface<int> TTL in seconds, -1 if no TTL, -2 if missing.
     */
    public function ttl(string $key): PromiseInterface
    {
        return $this->executeCommand(new TtlCommand([$key]));
    }

    /**
     * Returns the string representation of the type of value stored at key.
     *
     * @param string $key Key to inspect.
     *
     * @return PromiseInterface<string> Resolves to type string ('string', 'list', 'hash', etc.).
     */
    public function type(string $key): PromiseInterface
    {
        return $this->executeCommand(new TypeCommand([$key]));
    }

    /**
     * Asynchronously deletes keys in a background thread without blocking the server.
     *
     * @param string ...$keys One or more keys to unlink.
     *
     * @return PromiseInterface<int> Resolves to number of unlinked keys.
     */
    public function unlink(string ...$keys): PromiseInterface
    {
        return $this->executeCommand(new UnlinkCommand($keys));
    }

    /**
     * Iterates the set of keys in the currently selected Redis database.
     *
     * @param string|int $cursor The cursor to start the scan from (use '0' for a new scan).
     * @param string|null $match Glob-style pattern to match keys against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     * @param string|null $type Filter keys by type (e.g., 'string', 'hash', 'list').
     *
     * @return PromiseInterface<array{0: string, 1: array<int, string>}> Resolves to `[next_cursor, [key1, key2, ...]]`.
     */
    public function scan(string|int $cursor = '0', ?string $match = null, ?int $count = null, ?string $type = null): PromiseInterface
    {
        $args = [(string) $cursor];

        if ($match !== null) {
            $args[] = 'MATCH';
            $args[] = $match;
        }

        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        if ($type !== null) {
            $args[] = 'TYPE';
            $args[] = $type;
        }

        return $this->executeCommand(new ScanCommand($args));
    }

    /**
     * Renames a key. Overwrites the destination key if it already exists.
     *
     * @param string $key The key to rename.
     * @param string $newKey The new name for the key.
     *
     * @return PromiseInterface<string> Resolves to "OK" on success.
     */
    public function rename(string $key, string $newKey): PromiseInterface
    {
        return $this->executeCommand(new RenameCommand([$key, $newKey]));
    }

    /**
     * Renames a key, only if the new key does not exist.
     *
     * @param string $key The key to rename.
     * @param string $newKey The new name for the key.
     *
     * @return PromiseInterface<int> Resolves to 1 if key was renamed, 0 if new key already existed.
     */
    public function renamenx(string $key, string $newKey): PromiseInterface
    {
        return $this->executeCommand(new RenamenxCommand([$key, $newKey]));
    }

    /**
     * Removes the existing timeout on a key, turning the key from volatile to persistent.
     *
     * @param string $key Target key.
     *
     * @return PromiseInterface<int> Resolves to 1 if timeout was removed, 0 if key does not exist or has no associated timeout.
     */
    public function persist(string $key): PromiseInterface
    {
        return $this->executeCommand(new PersistCommand([$key]));
    }

    /**
     * Asynchronously streams keys using SCAN with automatic pre-fetching and backpressure.
     *
     * @param string|null $match Glob-style pattern to match keys against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     * @param string|null $type Filter keys by type.
     *
     * @return PromiseInterface<ScanStreamInterface<int, string>>
     */
    public function scanStream(?string $match = null, ?int $count = null, ?string $type = null): PromiseInterface
    {
        $fetcher = function (string $cursor) use ($match, $count, $type): PromiseInterface {
            $args = [$cursor];
            if ($match !== null) {
                $args[] = 'MATCH';
                $args[] = $match;
            }
            if ($count !== null) {
                $args[] = 'COUNT';
                $args[] = $count;
            }
            if ($type !== null) {
                $args[] = 'TYPE';
                $args[] = $type;
            }

            return $this->executeCommand(new ScanCommand($args));
        };

        $resultParser = static function (array $elements): array {
            /** @var list<array{0: null, 1: string}> */
            return array_map(static function (mixed $el): array {
                $str = \is_scalar($el) || $el instanceof \Stringable ? (string) $el : '';

                return [null, $str];
            }, $elements);
        };

        /** @var Promise<ScanStream<int, string>> $streamPromise */
        $streamPromise = new Promise();

        $initialPromise = $fetcher('0');
        $initialPromise->then(
            function (array $result) use ($streamPromise, $fetcher, $resultParser): void {
                if ($streamPromise->isCancelled()) {
                    return;
                }

                $cursor = (string) $result[0];
                $elements = $resultParser($result[1]);

                $streamPromise->resolve(new ScanStream($fetcher, $resultParser, $cursor, $elements));
            },
            function (\Throwable $e) use ($streamPromise): void {
                if (! $streamPromise->isSettled()) {
                    $streamPromise->reject($e);
                }
            }
        );

        Promise::forwardCancellation($streamPromise, $initialPromise);

        return $streamPromise;
    }
}
