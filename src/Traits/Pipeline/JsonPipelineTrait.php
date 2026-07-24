<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

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

trait JsonPipelineTrait
{
    /**
     * @template TResponse
     *
     * @param CommandInterface<TResponse> $command
     *
     * @return self
     */
    abstract public function executeCommand(CommandInterface $command): self;

    /**
     * {@inheritDoc}
     */
    public function jsonSet(string $key, string $path, mixed $value, ?string $exist = null): self
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
     */
    public function jsonGet(string $key, string ...$paths): self
    {
        $args = [$key, ...($paths === [] ? ['$'] : $paths)];

        return $this->executeCommand(new JsonGetCommand($args));
    }

    /**
     * {@inheritDoc}
     */
    public function jsonDel(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonDelCommand([$key, $path]));
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int, string> $keys
     */
    public function jsonMget(array $keys, string $path): self
    {
        return $this->executeCommand(new JsonMgetCommand([...$keys, $path]));
    }

    /**
     * {@inheritDoc}
     */
    public function jsonNumincrby(string $key, string $path, float|int $number): self
    {
        return $this->executeCommand(new JsonNumincrbyCommand([$key, $path, $number]));
    }

    /**
     * {@inheritDoc}
     */
    public function jsonArrappend(string $key, string $path, mixed ...$values): self
    {
        $encoded = array_map(static fn (mixed $v) => json_encode($v, JSON_THROW_ON_ERROR), $values);

        return $this->executeCommand(new JsonArrappendCommand([$key, $path, ...$encoded]));
    }

    /**
     * {@inheritDoc}
     */
    public function jsonType(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonTypeCommand([$key, $path]));
    }

    /**
     * {@inheritDoc}
     */
    public function jsonToggle(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonToggleCommand([$key, $path]));
    }

    /**
     * {@inheritDoc}
     */
    public function jsonClear(string $key, string $path = '$'): self
    {
        return $this->executeCommand(new JsonClearCommand([$key, $path]));
    }
}
