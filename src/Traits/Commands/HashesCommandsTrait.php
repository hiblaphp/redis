<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use Hibla\Redis\Command\Hashes\HdelCommand;
use Hibla\Redis\Command\Hashes\HexistsCommand;
use Hibla\Redis\Command\Hashes\HgetallCommand;
use Hibla\Redis\Command\Hashes\HgetCommand;
use Hibla\Redis\Command\Hashes\HincrbyCommand;
use Hibla\Redis\Command\Hashes\HincrbyfloatCommand;
use Hibla\Redis\Command\Hashes\HkeysCommand;
use Hibla\Redis\Command\Hashes\HlenCommand;
use Hibla\Redis\Command\Hashes\HmgetCommand;
use Hibla\Redis\Command\Hashes\HscanCommand;
use Hibla\Redis\Command\Hashes\HsetCommand;
use Hibla\Redis\Command\Hashes\HvalsCommand;
use Hibla\Redis\Interfaces\CommandInterface;
use Hibla\Redis\Internals\ScanStream;

trait HashesCommandsTrait
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
     * Returns the value associated with field in the hash stored at key.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     *
     * @return PromiseInterface<string|null> Field value or null if missing.
     */
    public function hget(string $key, string $field): PromiseInterface
    {
        return $this->executeCommand(new HgetCommand([$key, $field]));
    }

    /**
     * Sets specified fields to values in hash stored at key.
     *
     * @param string $key Hash key.
     * @param array<string, mixed> $fieldsAndValues Associative array of field/value pairs.
     *
     * @return PromiseInterface<int> Number of fields added.
     */
    public function hset(string $key, array $fieldsAndValues): PromiseInterface
    {
        $args = [$key];

        if (array_is_list($fieldsAndValues)) {
            foreach ($fieldsAndValues as $item) {
                $args[] = $item;
            }
        } else {
            foreach ($fieldsAndValues as $field => $value) {
                $args[] = (string) $field;
                $args[] = $value;
            }
        }

        return $this->executeCommand(new HsetCommand($args));
    }

    /**
     * Retrieves all fields and values of hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<array<string, string>> Associative array of fields and values.
     */
    public function hgetall(string $key): PromiseInterface
    {
        return $this->executeCommand(new HgetallCommand([$key]));
    }

    /**
     * Removes specified fields from hash stored at key.
     *
     * @param string $key Hash key.
     * @param string ...$fields Fields to remove.
     *
     * @return PromiseInterface<int> Number of fields removed.
     */
    public function hdel(string $key, string ...$fields): PromiseInterface
    {
        return $this->executeCommand(new HdelCommand([$key, ...$fields]));
    }

    /**
     * Returns if field exists in hash stored at key.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     *
     * @return PromiseInterface<int> 1 if field exists, 0 otherwise.
     */
    public function hexists(string $key, string $field): PromiseInterface
    {
        return $this->executeCommand(new HexistsCommand([$key, $field]));
    }

    /**
     * Returns values associated with fields in hash stored at key.
     *
     * @param string $key Hash key.
     * @param string ...$fields Fields to retrieve.
     *
     * @return PromiseInterface<array<int, string|null>> Array of values matching requested fields.
     */
    public function hmget(string $key, string ...$fields): PromiseInterface
    {
        return $this->executeCommand(new HmgetCommand([$key, ...$fields]));
    }

    /**
     * Increments the number stored at field in the hash stored at key by increment.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param int $increment Integer amount to increment by.
     *
     * @return PromiseInterface<int> Resolves to the value at field after the increment operation.
     */
    public function hincrby(string $key, string $field, int $increment): PromiseInterface
    {
        return $this->executeCommand(new HincrbyCommand([$key, $field, $increment]));
    }

    /**
     * Increment the specified field of a hash representing a floating point number by the specified increment.
     *
     * @param string $key Hash key.
     * @param string $field Field name.
     * @param float $increment Float amount to increment by.
     *
     * @return PromiseInterface<string> Resolves to the string representation of the new value.
     */
    public function hincrbyfloat(string $key, string $field, float $increment): PromiseInterface
    {
        return $this->executeCommand(new HincrbyfloatCommand([$key, $field, $increment]));
    }

    /**
     * Returns all field names in the hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<array<int, string>> List of fields in the hash, or an empty array if the key does not exist.
     */
    public function hkeys(string $key): PromiseInterface
    {
        return $this->executeCommand(new HkeysCommand([$key]));
    }

    /**
     * Returns all values in the hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<array<int, string>> List of values in the hash, or an empty array if the key does not exist.
     */
    public function hvals(string $key): PromiseInterface
    {
        return $this->executeCommand(new HvalsCommand([$key]));
    }

    /**
     * Returns the number of fields contained in the hash stored at key.
     *
     * @param string $key Hash key.
     *
     * @return PromiseInterface<int> Number of fields in the hash, or 0 when the key does not exist.
     */
    public function hlen(string $key): PromiseInterface
    {
        return $this->executeCommand(new HlenCommand([$key]));
    }

    /**
     * Iterates fields and values of a Hash type.
     *
     * @param string $key The hash key.
     * @param string|int $cursor The cursor to start the scan from.
     * @param string|null $match Glob-style pattern.
     * @param int|null $count A hint for the amount of work to do.
     *
     * @return PromiseInterface<array{0: string, 1: array<int, string>}>
     */
    public function hscan(string $key, string|int $cursor = '0', ?string $match = null, ?int $count = null): PromiseInterface
    {
        $args = [$key, (string) $cursor];

        if ($match !== null) {
            $args[] = 'MATCH';
            $args[] = $match;
        }

        if ($count !== null) {
            $args[] = 'COUNT';
            $args[] = $count;
        }

        return $this->executeCommand(new HscanCommand($args));
    }

    /**
     * Asynchronously streams fields and values of a Hash using HSCAN.
     *
     * @param string $key The hash key.
     * @param string|null $match Glob-style pattern to match field names against.
     * @param int|null $count A hint to Redis about how much work to do per scan iteration.
     *
     * @return PromiseInterface<ScanStream<string, string>> Yields field => value pairs.
     */
    public function hscanStream(string $key, ?string $match = null, ?int $count = null): PromiseInterface
    {
        $fetcher = function (string $cursor) use ($key, $match, $count): PromiseInterface {
            $args = [$key, $cursor];
            if ($match !== null) {
                $args[] = 'MATCH';
                $args[] = $match;
            }
            if ($count !== null) {
                $args[] = 'COUNT';
                $args[] = $count;
            }

            return $this->executeCommand(new HscanCommand($args));
        };

        $resultParser = static function (array $elements): array {
            $tuples = [];
            $total = \count($elements);
            for ($i = 0; $i < $total; $i += 2) {
                if (isset($elements[$i + 1])) {
                    $tuples[] = [$elements[$i], $elements[$i + 1]];
                }
            }

            return $tuples;
        };

        /** @var Promise<ScanStream<string, string>> $streamPromise */
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
