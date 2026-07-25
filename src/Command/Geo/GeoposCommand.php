<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Geo;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis GEOPOS command.
 *
 * Returns the positions (longitude, latitude) of all the specified members of the geospatial index at key.
 *
 * @see https://redis.io/commands/geopos/
 *
 * @extends AbstractCommand<array<int, array<int, string>|null>>
 */
final class GeoposCommand extends AbstractCommand
{
    public string $id {
        get => 'GEOPOS';
    }
}
