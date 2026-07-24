<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Json\JsonArrappendCommand;
use Hibla\Redis\Command\Json\JsonClearCommand;
use Hibla\Redis\Command\Json\JsonDelCommand;
use Hibla\Redis\Command\Json\JsonGetCommand;
use Hibla\Redis\Command\Json\JsonMgetCommand;
use Hibla\Redis\Command\Json\JsonNumincrbyCommand;
use Hibla\Redis\Command\Json\JsonSetCommand;
use Hibla\Redis\Command\Json\JsonToggleCommand;
use Hibla\Redis\Command\Json\JsonTypeCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait JsonCommandsTrait
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
     * {@inheritDoc}
     *
     * @return PromiseInterface<string|null>
     */
    public function jsonSet(string $key, string $path, mixed $value, ?string $exist = null): PromiseInterface
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        $args = [$key, $path, $encoded];

        if ($exist !== null) {
            $args[] = strtoupper($exist);
        }

        return $this->executeCommand(new JsonSetCommand($args));
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<mixed>
     */
    public function jsonGet(string $key, string ...$paths): PromiseInterface
    {
        $args = [$key, ...($paths === [] ? ['$'] : $paths)];

        return $this->executeCommand(new JsonGetCommand($args));
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<int>
     */
    public function jsonDel(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonDelCommand([$key, $path]));
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int, string> $keys
     *
     * @return PromiseInterface<array<int, mixed>>
     */
    public function jsonMget(array $keys, string $path): PromiseInterface
    {
        return $this->executeCommand(new JsonMgetCommand([...$keys, $path]));
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<mixed>
     */
    public function jsonNumincrby(string $key, string $path, float|int $number): PromiseInterface
    {
        return $this->executeCommand(new JsonNumincrbyCommand([$key, $path, $number]));
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<array<int, int|null>|int|null>
     */
    public function jsonArrappend(string $key, string $path, mixed ...$values): PromiseInterface
    {
        $encoded = array_map(static fn (mixed $v) => json_encode($v, JSON_THROW_ON_ERROR), $values);

        return $this->executeCommand(new JsonArrappendCommand([$key, $path, ...$encoded]));
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<array<int, string|null>|string|null>
     */
    public function jsonType(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonTypeCommand([$key, $path]));
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<array<int, int|bool|null>|int|bool|null>
     */
    public function jsonToggle(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonToggleCommand([$key, $path]));
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<int>
     */
    public function jsonClear(string $key, string $path = '$'): PromiseInterface
    {
        return $this->executeCommand(new JsonClearCommand([$key, $path]));
    }
}
