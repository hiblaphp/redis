<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Keys;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SCAN command.
 *
 * Iterates the set of keys in the currently selected Redis database.
 * Returns an array where the first element is the next cursor, and the second element is an array of keys.
 *
 * @see https://redis.io/commands/scan/
 *
 * @extends AbstractCommand<array{0: string, 1: array<int, string>}>
 */
final class ScanCommand extends AbstractCommand
{
    public string $id {
        get => 'SCAN';
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    public function parseResponse(mixed $data): array
    {
        if (! \is_array($data) || \count($data) < 2) {
            return ['0', []];
        }

        $cursorRaw = $data[0];
        $cursor = \is_scalar($cursorRaw) || $cursorRaw instanceof \Stringable ? (string) $cursorRaw : '0';

        $keysRaw = $data[1];
        $keys = [];

        if (\is_array($keysRaw)) {
            foreach ($keysRaw as $keyRaw) {
                if (\is_scalar($keyRaw) || $keyRaw instanceof \Stringable) {
                    $keys[] = (string) $keyRaw;
                }
            }
        }

        return [$cursor, $keys];
    }
}
