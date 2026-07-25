<?php

declare(strict_types=1);

namespace Hibla\Redis\Interfaces\Pipeline;

interface GeoPipelineInterface
{
    /**
     * Adds a GEOADD command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param array<string, array{0: float, 1: float}|array{longitude: float, latitude: float}> $locations Associative array of `['member' => [longitude, latitude]]`.
     *
     * @return self For method chaining.
     */
    public function geoadd(string $key, array $locations): self;

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
    public function geodist(string $key, string $member1, string $member2, ?string $unit = null): self;

    /**
     * Adds a GEOHASH command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param string ...$members Members to query.
     *
     * @return self For method chaining.
     */
    public function geohash(string $key, string ...$members): self;

    /**
     * Adds a GEOPOS command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param string ...$members Members to query.
     *
     * @return self For method chaining.
     */
    public function geopos(string $key, string ...$members): self;

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
    public function georadius(string $key, float $longitude, float $latitude, float $radius, string $unit = 'm', string ...$options): self;

    /**
     * Adds a GEOSEARCH command to the pipeline.
     *
     * @param string $key Geospatial index key.
     * @param string ...$options Search parameters and modifiers.
     *
     * @return self For method chaining.
     */
    public function geosearch(string $key, string ...$options): self;
}
