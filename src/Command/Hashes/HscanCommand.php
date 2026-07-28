<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Hashes;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis HSCAN command.
 *
 * Iterates fields and values of a Hash type.
 * Returns an array where the first element is the next cursor, and the second element is a flat array of fields and values.
 *
 * @see https://redis.io/commands/hscan/
 *
 * @extends AbstractCommand<array{0: string, 1: array<int, string>}>
 */
final class HscanCommand extends AbstractCommand
{
    public string $id {
        get => 'HSCAN';
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
