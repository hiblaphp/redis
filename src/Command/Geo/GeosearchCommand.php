<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Geo;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis GEOSEARCH command.
 *
 * Returns the members of a geospatial index stored at key matching a spatial query area.
 *
 * @see https://redis.io/commands/geosearch/
 *
 * @extends AbstractCommand<array<int, mixed>>
 */
final class GeosearchCommand extends AbstractCommand
{
    public string $id {
        get => 'GEOSEARCH';
    }
}
