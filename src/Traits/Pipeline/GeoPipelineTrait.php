<?php

declare(strict_types=1);

namespace Hibla\Redis\Traits\Pipeline;

use Hibla\Redis\Command\Geo\GeoaddCommand;
use Hibla\Redis\Command\Geo\GeodistCommand;
use Hibla\Redis\Command\Geo\GeohashCommand;
use Hibla\Redis\Command\Geo\GeoposCommand;
use Hibla\Redis\Command\Geo\GeoradiusCommand;
use Hibla\Redis\Command\Geo\GeosearchCommand;
use Hibla\Redis\Interfaces\CommandInterface;

trait GeoPipelineTrait
{
    /**
     * @template TResponse
     *
     * @param CommandInterface<TResponse> $command
     *
     * @return self
     */
    abstract public function executeCommand(CommandInterface $command): self;

    /**
     * Adds a GEOADD command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param float $longitude Longitude coordinate.
     * @param float $latitude Latitude coordinate.
     * @param string $member Member name.
     * @param mixed ...$additionalLongitudeLatitudeMembers Additional triplet(s) of [longitude, latitude, member].
     *
     * @return self For method chaining.
     */
    public function geoadd(string $key, float $longitude, float $latitude, string $member, mixed ...$additionalLongitudeLatitudeMembers): self
    {
        return $this->executeCommand(new GeoaddCommand([$key, $longitude, $latitude, $member, ...$additionalLongitudeLatitudeMembers]));
    }

    /**
     * Adds a GEODIST command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param string $member1 First member.
     * @param string $member2 Second member.
     * @param string|null $unit Optional distance unit ('m', 'km', 'ft', 'mi').
     *
     * @return self For method chaining.
     */
    public function geodist(string $key, string $member1, string $member2, ?string $unit = null): self
    {
        $args = [$key, $member1, $member2];
        if ($unit !== null) {
            $args[] = $unit;
        }

        return $this->executeCommand(new GeodistCommand($args));
    }

    /**
     * Adds a GEOHASH command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param string ...$members Members to query.
     *
     * @return self For method chaining.
     */
    public function geohash(string $key, string ...$members): self
    {
        return $this->executeCommand(new GeohashCommand([$key, ...$members]));
    }

    /**
     * Adds a GEOPOS command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param string ...$members Members to query.
     *
     * @return self For method chaining.
     */
    public function geopos(string $key, string ...$members): self
    {
        return $this->executeCommand(new GeoposCommand([$key, ...$members]));
    }

    /**
     * Adds a GEORADIUS command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param float $longitude Longitude coordinate.
     * @param float $latitude Latitude coordinate.
     * @param float $radius Radius distance.
     * @param string $unit Distance unit ('m', 'km', 'ft', 'mi').
     * @param string ...$options Optional modifiers ('WITHCOORD', 'WITHDIST', 'COUNT', etc.).
     *
     * @return self For method chaining.
     */
    public function georadius(string $key, float $longitude, float $latitude, float $radius, string $unit = 'm', string ...$options): self
    {
        return $this->executeCommand(new GeoradiusCommand([$key, $longitude, $latitude, $radius, $unit, ...$options]));
    }

    /**
     * Adds a GEOSEARCH command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param string ...$options Search parameters and modifiers.
     *
     * @return self For method chaining.
     */
    public function geosearch(string $key, string ...$options): self
    {
        return $this->executeCommand(new GeosearchCommand([$key, ...$options]));
    }
}
