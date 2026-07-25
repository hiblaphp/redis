<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Geo;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis GEODIST command.
 *
 * Returns the distance between two members in the geospatial index stored at key.
 *
 * @see https://redis.io/commands/geodist/
 *
 * @extends AbstractCommand<string|null>
 */
final class GeodistCommand extends AbstractCommand
{
    public string $id {
        get => 'GEODIST';
    }
}
