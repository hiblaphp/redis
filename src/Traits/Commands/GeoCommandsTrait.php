<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Commands;

use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Redis\Command\Geo\GeoaddCommand;
use Hibla\Redis\Command\Geo\GeodistCommand;
use Hibla\Redis\Command\Geo\GeohashCommand;
use Hibla\Redis\Command\Geo\GeoposCommand;
use Hibla\Redis\Command\Geo\GeoradiusCommand;
use Hibla\Redis\Command\Geo\GeosearchCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait GeoCommandsTrait
{
    /**
     * @template TReturn
     *
     * @param CommandInterface<TReturn> $command
     *
     * @return PromiseInterface<TReturn>
     */
    abstract public function executeCommand(CommandInterface $command): PromiseInterface;

    /**
     * Adds the specified geospatial items to the specified key.
     *
     * @param string $key Geospatial index key.
     * @param float $longitude Longitude coordinate.
     * @param float $latitude Latitude coordinate.
     * @param string $member Member name.
     * @param mixed ...$additionalLongitudeLatitudeMembers Additional triplet(s) of [longitude, latitude, member].
     *
     * @return PromiseInterface<int> Number of elements added.
     */
    public function geoadd(string $key, float $longitude, float $latitude, string $member, mixed ...$additionalLongitudeLatitudeMembers): PromiseInterface
    {
        return $this->executeCommand(new GeoaddCommand([$key, $longitude, $latitude, $member, ...$additionalLongitudeLatitudeMembers]));
    }

    /**
     * Returns the distance between two members in a geospatial index.
     *
     * @param string $key Geospatial index key.
     * @param string $member1 First member.
     * @param string $member2 Second member.
     * @param string|null $unit Distance unit: 'm' (meters), 'km' (kilometers), 'ft' (feet), or 'mi' (miles).
     *
     * @return PromiseInterface<string|null> Distance as string or null if missing.
     */
    public function geodist(string $key, string $member1, string $member2, ?string $unit = null): PromiseInterface
    {
        $args = [$key, $member1, $member2];
        if ($unit !== null) {
            $args[] = $unit;
        }

        return $this->executeCommand(new GeodistCommand($args));
    }

    /**
     * Returns valid Geohash strings representing the positions of requested members.
     *
     * @param string $key Geospatial index key.
     * @param string ...$members Members to query.
     *
     * @return PromiseInterface<array<int, string|null>> Array of Geohash strings matching member order.
     */
    public function geohash(string $key, string ...$members): PromiseInterface
    {
        return $this->executeCommand(new GeohashCommand([$key, ...$members]));
    }

    /**
     * Returns the positions (longitude, latitude) of specified members.
     *
     * @param string $key Geospatial index key.
     * @param string ...$members Members to query.
     *
     * @return PromiseInterface<array<int, array<int, string>|null>> Array of [longitude, latitude] pairs or null for missing members.
     */
    public function geopos(string $key, string ...$members): PromiseInterface
    {
        return $this->executeCommand(new GeoposCommand([$key, ...$members]));
    }

    /**
     * Returns members within a given radius from a longitude, latitude position.
     *
     * @param string $key Geospatial index key.
     * @param float $longitude Longitude coordinate.
     * @param float $latitude Latitude coordinate.
     * @param float $radius Radius distance.
     * @param string $unit Distance unit ('m', 'km', 'ft', 'mi').
     * @param string ...$options Optional modifiers (e.g., 'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 'asc'/'desc').
     *
     * @return PromiseInterface<array<int, mixed>> Array of matching members and requested optional data.
     */
    public function georadius(string $key, float $longitude, float $latitude, float $radius, string $unit = 'm', string ...$options): PromiseInterface
    {
        return $this->executeCommand(new GeoradiusCommand([$key, $longitude, $latitude, $radius, $unit, ...$options]));
    }

    /**
     * Queries a geospatial index area using a circular or box search.
     *
     * @param string $key Geospatial index key.
     * @param string ...$options Search parameters and modifiers (e.g. FROMMEMBER/FROMLONLAT, BYRADIUS/BYBOX, WITHCOORD, WITHDIST, etc.).
     *
     * @return PromiseInterface<array<int, mixed>> Array of matching members and requested optional data.
     */
    public function geosearch(string $key, string ...$options): PromiseInterface
    {
        return $this->executeCommand(new GeosearchCommand([$key, ...$options]));
    }
}
