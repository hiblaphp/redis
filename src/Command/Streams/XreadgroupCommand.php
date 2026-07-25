<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Streams;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis XREADGROUP command.
 *
 * Reads entries from a stream as part of a consumer group.
 * Parses raw RESP arrays into `[streamKey => [entry_id => [field => value]]]` format.
 *
 * @see https://redis.io/commands/xreadgroup/
 *
 * @extends AbstractCommand<array<string, array<string, array<string, string>>>|null>
 */
final class XreadgroupCommand extends AbstractCommand
{
    public string $id {
        get => 'XREADGROUP';
    }

    public function isBlocking(): bool
    {
        foreach ($this->args as $arg) {
            if ((\is_scalar($arg) || $arg instanceof \Stringable) && strtoupper((string) $arg) === 'BLOCK') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<string, array<string, string>>>|null
     */
    public function parseResponse(mixed $data): ?array
    {
        if (! \is_array($data)) {
            return null;
        }

        $result = [];
        foreach ($data as $streamData) {
            if (\is_array($streamData) && isset($streamData[0], $streamData[1]) && \is_array($streamData[1])) {
                $sKeyRaw = $streamData[0];
                $streamKey = \is_scalar($sKeyRaw) || $sKeyRaw instanceof \Stringable ? (string) $sKeyRaw : '';

                $entriesRaw = $streamData[1];
                $entries = [];

                foreach ($entriesRaw as $entry) {
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

                        $entries[$entryId] = $fields;
                    }
                }

                $result[$streamKey] = $entries;
            }
        }

        return $result;
    }
}
