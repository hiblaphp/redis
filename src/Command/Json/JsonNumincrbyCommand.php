<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.NUMINCRBY command.
 *
 * Increments the numeric value at path by number.
 *
 * @see https://redis.io/commands/json.numincrby/
 *
 * @extends AbstractCommand<mixed>
 */
final class JsonNumincrbyCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.NUMINCRBY';
    }

    public function parseResponse(mixed $data): mixed
    {
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
