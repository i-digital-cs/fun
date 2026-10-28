<?php

namespace App\Services\Location;

use App\Models\City;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for discovery origin (manual city / device GPS / nationwide).
 */
class ResolveDiscoveryOriginService
{
    /**
     * @param  array{
     *     city_id?: int|null,
     *     distance_km?: int|null,
     *     latitude?: float,
     *     longitude?: float,
     *     discovery_mode?: string|null
     * }  $filters
     * @return array{latitude: float, longitude: float, distance_km: int, city: ?City, origin_type: string}|null
     */
    public function resolve(?User $user, array $filters): ?array
    {
        $allowedDistances = config('location.distances_km');
        $distance = (int) ($filters['distance_km']
            ?? $user?->distance_km
            ?? config('location.default_distance_km'));

        if (! in_array($distance, $allowedDistances, true)) {
            throw ValidationException::withMessages([
                'distance_km' => [__('location.invalid_distance')],
            ]);
        }

        $mode = isset($filters['discovery_mode'])
            ? strtolower(trim((string) $filters['discovery_mode']))
            : null;

        if ($mode === 'nationwide') {
            return null;
        }

        if ($mode === 'device') {
            return $this->deviceOrigin($filters, $distance);
        }

        if ($mode === 'manual') {
            return $this->manualOrigin($filters, $distance);
        }

        // Legacy exclusive params (no discovery_mode):
        // city_id key present → manual/nationwide; else lat/lng → device; else profile city.
        $hasCityKey = array_key_exists('city_id', $filters);

        if ($hasCityKey) {
            return $this->manualOrigin($filters, $distance);
        }

        if (isset($filters['latitude'], $filters['longitude'])) {
            return $this->deviceOrigin($filters, $distance);
        }

        return $this->profileOrigin($user, $distance);
    }

    /**
     * @param  array{city_id?: int|null}  $filters
     * @return array{latitude: float, longitude: float, distance_km: int, city: City, origin_type: string}|null
     */
    private function manualOrigin(array $filters, int $distance): ?array
    {
        if (! array_key_exists('city_id', $filters) || $filters['city_id'] === null) {
            return null;
        }

        $city = City::query()->active()->find($filters['city_id']);
        if ($city === null) {
            throw ValidationException::withMessages([
                'city_id' => [__('location.city_unavailable')],
            ]);
        }

        return [
            'latitude' => (float) $city->latitude,
            'longitude' => (float) $city->longitude,
            'distance_km' => $distance,
            'city' => $city,
            'origin_type' => 'city',
        ];
    }

    /**
     * @param  array{latitude?: float, longitude?: float}  $filters
     * @return array{latitude: float, longitude: float, distance_km: int, city: null, origin_type: string}
     */
    private function deviceOrigin(array $filters, int $distance): array
    {
        if (! isset($filters['latitude'], $filters['longitude'])
            || ! is_numeric($filters['latitude'])
            || ! is_numeric($filters['longitude'])) {
            throw ValidationException::withMessages([
                'latitude' => [__('location.device_coords_required')],
            ]);
        }

        $lat = (float) $filters['latitude'];
        $lng = (float) $filters['longitude'];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw ValidationException::withMessages([
                'latitude' => [__('location.invalid_coordinates')],
            ]);
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_km' => $distance,
            'city' => null,
            'origin_type' => 'device',
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, distance_km: int, city: City, origin_type: string}|null
     */
    private function profileOrigin(?User $user, int $distance): ?array
    {
        $cityId = $user?->city_id;
        if ($cityId === null) {
            return null;
        }

        $city = City::query()->active()->find($cityId);
        if ($city === null) {
            throw ValidationException::withMessages([
                'city_id' => [__('location.city_unavailable')],
            ]);
        }

        return [
            'latitude' => (float) $city->latitude,
            'longitude' => (float) $city->longitude,
            'distance_km' => $distance,
            'city' => $city,
            'origin_type' => 'city',
        ];
    }
}
