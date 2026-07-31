<?php

declare(strict_types=1);

namespace Hibla\Redis\Cluster;

use Hibla\Redis\Interfaces\CommandInterface;

final class KeyExtractor
{
    /**
     * @param CommandInterface<mixed> $command
     */
    public static function extract(CommandInterface $command): ?string
    {
        $id = strtoupper($command->id);
        $args = $command->arguments;

        if ($args === []) {
            return null;
        }

        $standardKeyCommands = [
            'GET', 'SET', 'SETEX', 'SETNX', 'DEL', 'EXISTS', 'EXPIRE', 'TTL', 'TYPE',
            'HGET', 'HSET', 'HGETALL', 'HDEL', 'HMGET', 'HINCRBY', 'HSCAN',
            'SADD', 'SREM', 'SMEMBERS', 'SISMEMBER', 'SCARD',
            'ZADD', 'ZREM', 'ZRANGE', 'ZSCORE', 'ZINCRBY', 'ZSCAN',
            'LPUSH', 'RPUSH', 'LPOP', 'RPOP', 'LLEN', 'LRANGE',
            'XADD', 'XREAD', 'XREADGROUP', 'XRANGE',
            'JSON.SET', 'JSON.GET', 'JSON.DEL',
        ];

        if (in_array($id, $standardKeyCommands, true)) {
            $key = $args[0];

            return \is_scalar($key) || $key instanceof \Stringable ? (string) $key : null;
        }

        if ($id === 'MSET') {
            return \is_scalar($args[0]) || $args[0] instanceof \Stringable ? (string) $args[0] : null;
        }

        if ($id === 'EVAL' || $id === 'EVALSHA') {
            if (isset($args[1], $args[2]) && \is_numeric($args[1]) && (int) $args[1] > 0) {
                return \is_scalar($args[2]) || $args[2] instanceof \Stringable ? (string) $args[2] : null;
            }

            return null;
        }

        $firstArg = $args[0];
        if (\is_scalar($firstArg) || $firstArg instanceof \Stringable) {
            return (string) $firstArg;
        }

        return null;
    }
}
