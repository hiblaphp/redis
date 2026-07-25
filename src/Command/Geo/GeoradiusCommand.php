<?php

declare(strict_types=1);

namespace Hibla\Redis\Command\Geo;

use Hibla\Redis\Command\AbstractCommand;

/**
 * Redis GEORADIUS command.
 *
 * Returns the members of a geospatial index centered at specified coordinates within radius distance.
 *
 * @see https://redis.io/commands/georadius/
 *
 * @extends AbstractCommand<array<int, mixed>>
 */
final class GeoradiusCommand extends AbstractCommand
{
    public string $id {
        get => 'GEORADIUS';
    }
}
