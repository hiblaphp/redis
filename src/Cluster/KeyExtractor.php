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
        if (! $command->hasKeys()) {
            return null;
        }

        $args = $command->arguments;
        if ($args === []) {
            return null;
        }

        $id = strtoupper($command->id);

        /**
         * BITOP and XGROUP: Key is the SECOND argument (args[1])
         * BITOP <op> <destkey> <srckey...>
         * XGROUP CREATE <stream_key> <group...>
         */
        if ($id === 'BITOP' || $id === 'XGROUP') {
            if (isset($args[1]) && (\is_scalar($args[1]) || $args[1] instanceof \Stringable)) {
                return (string) $args[1];
            }

            return null;
        }

        /**
         * Stream Read commands (XREAD / XREADGROUP): Key follows the 'STREAMS' token
         * XREAD COUNT 10 STREAMS <stream_key> 0-0
         */
        if ($id === 'XREAD' || $id === 'XREADGROUP') {
            $stringArgs = array_map(static fn (mixed $a) => \is_scalar($a) || $a instanceof \Stringable ? strtoupper((string) $a) : '', $args);
            $streamsIndex = array_search('STREAMS', $stringArgs, true);

            if (\is_int($streamsIndex) && isset($args[$streamsIndex + 1])) {
                $key = $args[$streamsIndex + 1];

                return \is_scalar($key) || $key instanceof \Stringable ? (string) $key : null;
            }
        }

        /**
         * Lua Scripting commands (EVAL / EVALSHA / EVAL_RO / EVALSHA_RO)
         * EVAL <script> <numkeys> <key1> <key2> ...
         */
        if ($id === 'EVAL' || $id === 'EVALSHA' || $id === 'EVAL_RO' || $id === 'EVALSHA_RO') {
            if (isset($args[1], $args[2]) && \is_numeric($args[1]) && (int) $args[1] > 0) {
                return \is_scalar($args[2]) || $args[2] instanceof \Stringable ? (string) $args[2] : null;
            }

            return null;
        }

        /**
         * Default: First argument is key
         * Covers GET, SET, HSET, ZADD, LPUSH, SADD, GEO*, JSON.*, TS.*, etc.
         */
        $firstArg = $args[0];
        if (\is_scalar($firstArg) || $firstArg instanceof \Stringable) {
            return (string) $firstArg;
        }

        return null;
    }
}
