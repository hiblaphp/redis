<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Geo;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis GEOADD command.
 *
 * Adds the specified geospatial items (longitude, latitude, name) to the specified key.
 *
 * @see https://redis.io/commands/geoadd/
 *
 * @extends AbstractCommand<int>
 */
final class GeoaddCommand extends AbstractCommand
{
    public string $id {
        get => 'GEOADD';
    }
}
