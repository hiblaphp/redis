<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.MGET command.
 *
 * Retrieves the JSON values at path for multiple keys.
 *
 * @see https://redis.io/commands/json.mget/
 *
 * @extends AbstractCommand<array<int, mixed>>
 */
final class JsonMgetCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.MGET';
    }

    /**
     * @return array<int, mixed>
     */
    public function parseResponse(mixed $data): array
    {
        if (! \is_array($data)) {
            return [];
        }

        $result = [];

        foreach ($data as $item) {
            if (! \is_string($item) || $item === '') {
                $result[] = null;

                continue;
            }

            try {
                $result[] = json_decode($item, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
