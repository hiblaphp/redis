<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Sets;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis SSCAN command.
 *
 * Iterates members of a Set type.
 * Returns an array where the first element is the next cursor, and the second element is an array of members.
 *
 * @see https://redis.io/commands/sscan/
 *
 * @extends AbstractCommand<array{0: string, 1: array<int, string>}>
 */
final class SscanCommand extends AbstractCommand
{
    public string $id {
        get => 'SSCAN';
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