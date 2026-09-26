<?php

namespace App\Services\Maps;

use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Free place autocomplete via Photon (OSM) — no Google Places key.
 */
class SuggestPlacesService
{
    /**
     * @return list<array{label: string, latitude: float, longitude: float}>
     */
    public function suggest(
        string $query,
        ?int $cityId = null,
        int $limit = 8,
    ): array {
        $q = trim($query);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $lat = null;
        $lng = null;

        if ($cityId !== null) {
            $city = City::query()->find($cityId);
            if ($city?->latitude !== null && $city?->longitude !== null) {
                $lat = (float) $city->latitude;
                $lng = (float) $city->longitude;
            }
        }

        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

        try {
            $params = [
                'q' => $q,
                'limit' => $limit,
                'lang' => $locale,
            ];
            if ($lat !== null && $lng !== null) {
                $params['lat'] = $lat;
                $params['lon'] = $lng;
            }

            $response = Http::withHeaders([
                'User-Agent' => 'Funity/1.0 (https://www.funnity.com; place-suggest)',
                'Accept-Language' => $locale === 'ar' ? 'ar,en' : 'en,ar',
            ])
                ->timeout(6)
                ->get('https://photon.komoot.io/api/', $params);

            if (! $response->successful()) {
                return $this->nominatimFallback($q, $lat, $lng, $limit, $locale);
            }

            $features = $response->json('features');
            if (! is_array($features)) {
                return [];
            }

            $out = [];
            foreach ($features as $feature) {
                if (! is_array($feature)) {
                    continue;
                }
                $coords = $feature['geometry']['coordinates'] ?? null;
                if (! is_array($coords) || count($coords) < 2) {
                    continue;
                }
                $longitude = (float) $coords[0];
                $latitude = (float) $coords[1];
                if (! $this->valid($latitude, $longitude)) {
                    continue;
                }
                $props = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
                $label = $this->labelFromPhoton($props);
                if ($label === '') {
                    continue;
                }
                $out[] = [
                    'label' => $label,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('places.suggest_failed', ['error' => $e->getMessage()]);

            return $this->nominatimFallback($q, $lat, $lng, $limit, $locale);
        }
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function labelFromPhoton(array $props): string
    {
        $parts = array_values(array_filter([
            $props['name'] ?? null,
            $props['street'] ?? $props['housenumber'] ?? null,
            $props['district'] ?? $props['suburb'] ?? $props['neighbourhood'] ?? null,
            $props['city'] ?? $props['town'] ?? $props['village'] ?? $props['county'] ?? null,
            $props['state'] ?? null,
            $props['country'] ?? null,
        ], fn ($p) => filled($p)));

        // Avoid "name, name" duplication when street equals name.
        $parts = array_values(array_unique(array_map(fn ($p) => trim((string) $p), $parts)));

        return implode(' · ', array_slice($parts, 0, 4));
    }

    /**
     * @return list<array{label: string, latitude: float, longitude: float}>
     */
    private function nominatimFallback(
        string $q,
        ?float $lat,
        ?float $lng,
        int $limit,
        string $locale,
    ): array {
        try {
            $params = [
                'q' => $q,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'limit' => $limit,
            ];
            if ($lat !== null && $lng !== null) {
                // Soft bias ~1° around city center.
                $params['viewbox'] = ($lng - 0.6).','.($lat + 0.6).','.($lng + 0.6).','.($lat - 0.6);
                $params['bounded'] = 0;
            }

            $response = Http::withHeaders([
                'User-Agent' => 'Funity/1.0 (https://www.funnity.com; place-suggest)',
                'Accept-Language' => $locale === 'ar' ? 'ar,en' : 'en,ar',
            ])
                ->timeout(6)
                ->get('https://nominatim.openstreetmap.org/search', $params);

            if (! $response->successful()) {
                return [];
            }

            $rows = $response->json();
            if (! is_array($rows)) {
                return [];
            }

            $out = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $latitude = (float) ($row['lat'] ?? 0);
                $longitude = (float) ($row['lon'] ?? 0);
                if (! $this->valid($latitude, $longitude)) {
                    continue;
                }
                $display = (string) ($row['display_name'] ?? '');
                $bits = array_slice(array_map('trim', explode(',', $display)), 0, 4);
                $label = implode(' · ', array_filter($bits));
                if ($label === '') {
                    continue;
                }
                $out[] = [
                    'label' => $label,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ];
            }

            return $out;
        } catch (\Throwable) {
            return [];
        }
    }

    private function valid(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
            && ! ($lat == 0.0 && $lng == 0.0);
    }
}
