<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.GET command.
 *
 * Retrieves the JSON value at key and optional path(s), automatically
 * parsing the raw JSON string into a PHP value/array.
 *
 * @see https://redis.io/commands/json.get/
 *
 * @extends AbstractCommand<mixed>
 */
final class JsonGetCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.GET';
    }

    public function parseResponse(mixed $data): mixed
    {
        if (! \is_string($data) || $data === '') {
            return null;
        }

        try {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $data;
        }
    }
}
