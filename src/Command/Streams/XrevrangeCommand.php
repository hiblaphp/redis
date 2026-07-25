<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XREVRANGE command.
 *
 * Returns stream entries matching a range of IDs in reverse order.
 *
 * @see https://redis.io/commands/xrevrange/
 *
 * @extends AbstractCommand<array<string, array<string, string>>>
 */
final class XrevrangeCommand extends AbstractCommand
{
    public string $id {
        get => 'XREVRANGE';
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function parseResponse(mixed $data): array
    {
        if (! \is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $entry) {
            if (\is_array($entry) && isset($entry[0], $entry[1]) && \is_array($entry[1])) {
                $eIdRaw = $entry[0];
                $entryId = \is_scalar($eIdRaw) || $eIdRaw instanceof \Stringable ? (string) $eIdRaw : '';

                $fieldsRaw = $entry[1];
                $fields = [];
                $count = \count($fieldsRaw);

                for ($i = 0; $i < $count; $i += 2) {
                    if (isset($fieldsRaw[$i + 1])) {
                        $fRaw = $fieldsRaw[$i];
                        $vRaw = $fieldsRaw[$i + 1];

                        $f = \is_scalar($fRaw) || $fRaw instanceof \Stringable ? (string) $fRaw : '';
                        $v = \is_scalar($vRaw) || $vRaw instanceof \Stringable ? (string) $vRaw : '';

                        $fields[$f] = $v;
                    }
                }

                $result[$entryId] = $fields;
            }
        }

        return $result;
    }
}
