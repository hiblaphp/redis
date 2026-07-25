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
     * @param array<string, array{0: float, 1: float}|array{longitude: float, latitude: float}> $locations Associative array of `['member' => [longitude, latitude]]`.
     *
     * @return self For method chaining.
     */
    public function geoadd(string $key, array $locations): self
    {
        $args = [$key];

        if (array_is_list($locations)) {
            foreach ($locations as $item) {
                if (\is_array($item)) {
                    $args[] = $item[0] ?? 0.0;
                    $args[] = $item[1] ?? 0.0;
                    $args[] = $item[2] ?? '';
                } else {
                    $args[] = $item;
                }
            }
        } else {
            foreach ($locations as $member => $coords) {
                if (\is_array($coords)) {
                    $longitude = $coords[0] ?? $coords['longitude'] ?? 0.0;
                    $latitude = $coords[1] ?? $coords['latitude'] ?? 0.0;
                    $args[] = $longitude;
                    $args[] = $latitude;
                    $args[] = (string) $member;
                }
            }
        }

        return $this->executeCommand(new GeoaddCommand($args));
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