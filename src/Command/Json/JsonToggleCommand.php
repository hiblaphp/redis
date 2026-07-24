<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Json;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis JSON.TOGGLE command.
 *
 * Toggles a boolean value at path.
 *
 * @see https://redis.io/commands/json.toggle/
 *
 * @extends AbstractCommand<array<int, bool|null>|bool|null>
 */
final class JsonToggleCommand extends AbstractCommand
{
    public string $id {
        get => 'JSON.TOGGLE';
    }

    /**
     * @return array<int, bool|null>|bool|null
     */
    public function parseResponse(mixed $data): mixed
    {
        // For legacy path notation (returns single scalar integer)
        if (\is_int($data)) {
            return $data === 1;
        }

        // For JSONPath notation (returns array of results)
        if (\is_array($data)) {
            /** @var array<int, bool|null> */
            return array_map(static function (mixed $item): ?bool {
                if (\is_int($item)) {
                    return $item === 1;
                }

                return null; // Null is returned for paths that are not booleans
            }, $data);
        }

        return null;
    }
}
