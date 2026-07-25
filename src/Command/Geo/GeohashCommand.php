<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Geo;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis GEOHASH command.
 *
 * Returns valid Geohash strings representing the positions of one or more elements in a geospatial index.
 *
 * @see https://redis.io/commands/geohash/
 *
 * @extends AbstractCommand<array<int, string|null>>
 */
final class GeohashCommand extends AbstractCommand
{
    public string $id {
        get => 'GEOHASH';
    }
}
