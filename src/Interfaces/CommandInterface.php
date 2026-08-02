<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces;

/**
 * Contract for all Redis commands.
 *
 * @template-covariant TResponse The expected PHP return type after parsing.
 */
interface CommandInterface
{
    /**
     * Indicates whether this command operates on Redis keys.
     */
    public function hasKeys(): bool;

    /**
     * The Redis command ID (e.g., 'GET', 'SET', 'HGETALL').
     */
    public string $id { get; }

    /**
     * The arguments to be sent alongside the command ID.
     *
     * @var array<int|string, mixed>
     */
    public array $arguments { get; }

    /**
     * Indicates if this command blocks the Redis connection (e.g. BLPOP).
     */
    public function isBlocking(): bool;

    /**
     * Parses the raw RESP response from the server into a PHP-friendly format.
     *
     * @return TResponse
     */
    public function parseResponse(mixed $data): mixed;
}
