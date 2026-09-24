<?php

namespace App\Services\Events;

use App\Models\City;
use App\Support\MapsUrlParser;
use Illuminate\Support\Facades\Http;

class ResolveEventLocationService
{
    public function __construct(
        private readonly MapsUrlParser $parser,
    ) {}

    /**
     * Normalize venue pin from explicit coords and/or a pasted maps URL.
     * When the pin is set but venue is empty/placeholder, reverse-geocode a free label (Nominatim).
     *
     * @param  array<string, mixed>  $data
     * @return array{latitude: float|null, longitude: float|null, maps_url: string|null, venue?: string|null}
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
        $venue = array_key_exists('venue', $data) ? trim((string) ($data['venue'] ?? '')) : null;

        $hadExplicitPin = $lat !== null && $lng !== null;
        $parsedFromLink = null;

        if ($mapsUrl !== null) {
            $parsedFromLink = $this->parser->parse($mapsUrl);
        }

        // Link fills missing pin; explicit pin always wins when both are present.
        if (! $hadExplicitPin && $parsedFromLink !== null) {
            $lat = $parsedFromLink['latitude'];
            $lng = $parsedFromLink['longitude'];
            $mapsUrl = $parsedFromLink['maps_url'];
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

        if ($lat !== null && $lng !== null) {
            // Keep pasted link only when it matches the pin (~80m). Otherwise sync URL → pin.
            if ($parsedFromLink !== null && $this->coordsMatch(
                $lat,
                $lng,
                $parsedFromLink['latitude'],
                $parsedFromLink['longitude']
            )) {
                $mapsUrl = $parsedFromLink['maps_url'];
            } else {
                $mapsUrl = $this->parser->openStreetMapUrl($lat, $lng);
            }
        }

        $out = [
            'latitude' => $lat,
            'longitude' => $lng,
            'maps_url' => $mapsUrl,
        ];

        // Only touch venue when the request included it (create/edit forms always do).
        if (array_key_exists('venue', $data)) {
            $pinMovedAwayFromLink = $hadExplicitPin
                && $parsedFromLink !== null
                && $lat !== null
                && $lng !== null
                && ! $this->coordsMatch(
                    $lat,
                    $lng,
                    $parsedFromLink['latitude'],
                    $parsedFromLink['longitude']
                );

            if ($pinMovedAwayFromLink) {
                // Pin is authoritative — refresh the place name from the marker.
                $out['venue'] = $this->reverseGeocode($lat, $lng)
                    ?? $this->cityFallbackName($cityId)
                    ?? $this->normalizeVenue((string) ($venue ?? ''), $lat, $lng, $cityId);
            } else {
                $out['venue'] = $this->normalizeVenue((string) ($venue ?? ''), $lat, $lng, $cityId);
            }
        }

        return $out;
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

    private function normalizeVenue(string $venue, ?float $lat, ?float $lng, ?int $cityId): ?string
    {
        if (! $this->isPlaceholderVenue($venue)) {
            return $venue === '' ? null : $venue;
        }

        if ($lat !== null && $lng !== null) {
            return $this->reverseGeocode($lat, $lng) ?? $this->cityFallbackName($cityId);
        }

        return $this->cityFallbackName($cityId);
    }

    private function isPlaceholderVenue(string $venue): bool
    {
        $normalized = mb_strtolower(trim($venue));
        if ($normalized === '') {
            return true;
        }

        return in_array($normalized, [
            'venue',
            'location',
            'place',
            'المكان',
            'موقع',
            'title',
            'العنوان',
        ], true);
    }

    private function cityFallbackName(?int $cityId): ?string
    {
        if (! $cityId) {
            return null;
        }

        $city = City::query()->find($cityId);

        return $city?->localizedName();
    }

    private function coordsMatch(float $latA, float $lngA, float $latB, float $lngB, float $toleranceDegrees = 0.0008): bool
    {
        return abs($latA - $latB) <= $toleranceDegrees
            && abs($lngA - $lngB) <= $toleranceDegrees;
    }

    private function reverseGeocode(float $lat, float $lng): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Funity/1.0 (https://www.funnity.com; event-place)',
                'Accept-Language' => app()->getLocale() === 'ar' ? 'ar,en' : 'en,ar',
            ])
                ->timeout(6)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'jsonv2',
                    'zoom' => 16,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $address = is_array($json['address'] ?? null) ? $json['address'] : [];

            $parts = array_values(array_filter([
                $address['amenity'] ?? $address['leisure'] ?? $address['tourism'] ?? $address['building'] ?? null,
                $address['road'] ?? $address['pedestrian'] ?? $address['neighbourhood'] ?? $address['suburb'] ?? null,
                $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['state'] ?? null,
            ], fn ($p) => filled($p)));

            if ($parts !== []) {
                return implode(' · ', array_unique($parts));
            }

            $name = $json['name'] ?? null;
            if (filled($name)) {
                return (string) $name;
            }

            $display = $json['display_name'] ?? null;
            if (filled($display)) {
                $bits = array_slice(array_map('trim', explode(',', (string) $display)), 0, 3);

                return implode(' · ', $bits);
            }
        } catch (\Throwable) {
            // Free reverse geocode is best-effort.
        }

        return null;
    }
}
