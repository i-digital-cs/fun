<?php

namespace App\Services\Events;

use App\Models\City;
use App\Support\MapsUrlParser;

class ResolveEventLocationService
{
    public function __construct(
        private readonly MapsUrlParser $parser,
    ) {}

    /**
     * Normalize venue pin from explicit coords and/or a pasted maps URL.
     *
     * @param  array<string, mixed>  $data
     * @return array{latitude: float|null, longitude: float|null, maps_url: string|null}
     */
    public function resolve(array $data, ?int $cityId = null): array
    {
        $lat = isset($data['latitude']) && $data['latitude'] !== '' && $data['latitude'] !== null
            ? (float) $data['latitude']
            : null;
        $lng = isset($data['longitude']) && $data['longitude'] !== '' && $data['longitude'] !== null
            ? (float) $data['longitude']
            : null;
        $mapsUrl = isset($data['maps_url']) ? trim((string) $data['maps_url']) : '';
        $mapsUrl = $mapsUrl !== '' ? $mapsUrl : null;

        if (($lat === null || $lng === null) && $mapsUrl !== null) {
            $parsed = $this->parser->parse($mapsUrl);
            if ($parsed !== null) {
                $lat = $parsed['latitude'];
                $lng = $parsed['longitude'];
                $mapsUrl = $parsed['maps_url'];
            }
        }

        if ($lat !== null && $lng !== null) {
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $lat = null;
                $lng = null;
            }
        } else {
            $lat = null;
            $lng = null;
        }

        // If we have coords but no share URL, store a free OSM link.
        if ($lat !== null && $lng !== null && $mapsUrl === null) {
            $mapsUrl = $this->parser->openStreetMapUrl($lat, $lng);
        }

        // Seed nothing from city automatically into DB — only used by the map UI.
        unset($cityId);

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'maps_url' => $mapsUrl,
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function cityCenter(?int $cityId): ?array
    {
        if (! $cityId) {
            return null;
        }

        $city = City::query()->find($cityId);
        if (! $city || $city->latitude === null || $city->longitude === null) {
            return null;
        }

        return [
            'latitude' => (float) $city->latitude,
            'longitude' => (float) $city->longitude,
        ];
    }
}
