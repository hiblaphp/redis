<?php

declare(strict_types=1);

use Hibla\Redis\Interfaces\PipelineInterface;
use Hibla\Redis\RedisClient;

use function Hibla\await;

describe('RedisClient - Geo Commands', function (): void {

    it('can add geospatial locations and query distances, hashes, and positions', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'geo_cities_' . uniqid();

            $added = await($client->geoadd($key, 13.361389, 38.115556, 'Palermo', 15.087269, 37.502669, 'Catania'));
            expect($added)->toBe(2);

            $distKm = await($client->geodist($key, 'Palermo', 'Catania', 'km'));
            expect($distKm)->not->toBeNull();
            expect((float) $distKm)->toBeGreaterThan(160)->toBeLessThan(170);

            $hashes = await($client->geohash($key, 'Palermo', 'Catania', 'NonExisting'));
            expect($hashes)->toHaveCount(3)
                ->and($hashes[0])->toBeString()
                ->and($hashes[1])->toBeString()
                ->and($hashes[2])->toBeNull()
            ;

            $positions = await($client->geopos($key, 'Palermo', 'NonExisting'));
            expect($positions)->toHaveCount(2)
                ->and($positions[0])->toBeArray()
                ->and((float) $positions[0][0])->toBeGreaterThan(13.0)->toBeLessThan(14.0)
                ->and($positions[1])->toBeNull()
            ;
        } finally {
            $client->close();
        }
    });

    it('can execute GEORADIUS and GEOSEARCH queries', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'geo_search_' . uniqid();

            await($client->geoadd($key, 13.361389, 38.115556, 'Palermo', 15.087269, 37.502669, 'Catania'));

            $radiusResults = await($client->georadius($key, 15.0, 37.5, 200, 'km'));
            expect($radiusResults)->toContain('Palermo')->toContain('Catania');

            $searchResults = await($client->geosearch($key, 'FROMLONLAT', '15.0', '37.5', 'BYRADIUS', '100', 'km'));
            expect($searchResults)->toContain('Catania');
        } finally {
            $client->close();
        }
    });

    it('can pipeline Geo commands', function () {
        $client = new RedisClient(getConfig());

        try {
            $key = 'geo_pipe_' . uniqid();

            $results = await($client->pipeline(function (PipelineInterface $pipe) use ($key) {
                $pipe->geoadd($key, 13.361389, 38.115556, 'Palermo')
                    ->geodist($key, 'Palermo', 'Palermo', 'm')
                    ->geopos($key, 'Palermo')
                ;
            }));

            expect($results)->toHaveCount(3)
                ->and($results[0])->toBe(1)
                ->and((float) $results[1])->toBe(0.0)
                ->and($results[2])->toBeArray()
            ;
        } finally {
            $client->close();
        }
    });
});
