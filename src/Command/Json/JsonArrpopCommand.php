<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.ARRPOP command.
 *
 * Removes and returns an element from the index in the array.
 *
 * @see https://redis.io/commands/json.arrpop/
 *
 * @extends AbstractCommand<mixed>
 */
final class JsonArrpopCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.ARRPOP';
    }

    public function parseResponse(mixed $data): mixed
    {
        if (\is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                if (\is_string($item) && $item !== '') {
                    try {
                        $result[] = json_decode($item, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        $result[] = $item;
                    }
                } else {
                    $result[] = $item;
                }
            }

            return $result;
        }

        if (! \is_string($data) || $data === '') {
            return $data;
        }

        try {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $data;
        }
    }
}
