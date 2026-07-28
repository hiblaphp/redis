<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\SortedSets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis ZSCAN command.
 *
 * Iterates members and scores of a Sorted Set type.
 * Returns an array where the first element is the next cursor, and the second element is a flat array of members and scores.
 *
 * @see https://redis.io/commands/zscan/
 *
 * @extends AbstractCommand<array{0: string, 1: array<int, string>}>
 */
final class ZscanCommand extends AbstractCommand
{
    public string $id {
        get => 'ZSCAN';
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

        $elementsRaw = $data[1];
        $elements = [];

        if (\is_array($elementsRaw)) {
            foreach ($elementsRaw as $elementRaw) {
                if (\is_scalar($elementRaw) || $elementRaw instanceof \Stringable) {
                    $elements[] = (string) $elementRaw;
                }
            }
        }

        return [$cursor, $elements];
    }
}