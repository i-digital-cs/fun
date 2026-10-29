<?php

namespace App\Services\Location;

use App\Models\City;
use App\Support\GeoDistance;
use Illuminate\Support\Collection;

/**
 * Shared origin→nearby-cities geography for list + map discovery.
 */
class NearbyDiscoveryGeography
{
    /**
     * Active city IDs whose centroids fall within $maxKm of the origin.
     *
     * @return Collection<int, int>
     */
    public function nearbyCityIds(float $latitude, float $longitude, int $maxKm): Collection
    {
        if ($maxKm <= 0) {
            return City::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->values();
        }

        $version = (int) cache()->get('nearby_cities_version', 0);
        $cacheKey = sprintf('nearby_cities:v%d:%.5f:%.5f:%d', $version, $latitude, $longitude, $maxKm);

        return cache()->remember($cacheKey, now()->addMinutes(10), function () use ($latitude, $longitude, $maxKm) {
            $ids = City::query()
                ->active()
                ->get(['id', 'latitude', 'longitude'])
                ->filter(fn (City $city) => GeoDistance::isWithinKm(
                    $latitude,
                    $longitude,
                    (float) $city->latitude,
                    (float) $city->longitude,
                    $maxKm
                ))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $originId = City::query()
                ->active()
                ->where('latitude', $latitude)
                ->where('longitude', $longitude)
                ->value('id');

            if ($originId !== null && ! $ids->contains((int) $originId)) {
                $ids->prepend((int) $originId);
            }

            return $ids->unique()->values();
        });
    }

    /**
     * Bounding box covering a radius (km) around an origin, with padding.
     *
     * @return array{north: float, south: float, east: float, west: float}
     */
    public function boundsForRadius(float $latitude, float $longitude, int $distanceKm, float $padFactor = 1.2): array
    {
        $km = max(1, $distanceKm) * $padFactor;
        $latPad = $km / 111.0;
        $lngPad = $km / (111.0 * max(0.2, cos(deg2rad($latitude))));

        return [
            'north' => min(90.0, $latitude + $latPad),
            'south' => max(-90.0, $latitude - $latPad),
            'east' => min(180.0, $longitude + $lngPad),
            'west' => max(-180.0, $longitude - $lngPad),
        ];
    }

    /**
     * Radius used for city-centroid eligibility (strict search).
     * Nationwide (0) is unchanged; otherwise subtract city uncertainty.
     */
    public function citySearchRadiusKm(int $selectedKm): int
    {
        if ($selectedKm <= 0) {
            return 0;
        }

        $uncertainty = max(0, (int) config('location.city_location_uncertainty_km', 10));

        return max(0, $selectedKm - $uncertainty);
    }

    /**
     * Progressive radii strictly wider than the selected radius (0 = nationwide).
     *
     * @return list<int>
     */
    public function progressiveRadii(int $selectedKm): array
    {
        $ladder = config('location.progressive_radii_km', config('location.expansion_ladder_km', [50, 100, 200, 0]));
        $out = [];
        foreach ($ladder as $km) {
            $km = (int) $km;
            if ($km === 0) {
                if ($selectedKm !== 0) {
                    $out[] = 0;
                }
                continue;
            }
            if ($selectedKm === 0 || $km > $selectedKm) {
                $out[] = $km;
            }
        }

        return array_values(array_unique($out));
    }
}
