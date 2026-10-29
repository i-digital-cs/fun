<?php

namespace App\Services\Location;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reverse-geocode device/manual coordinates into a human-readable label.
 * Nominatim (OSM) — best-effort, throttled at the controller.
 */
class ResolveLocationLabelService
{
    /**
     * @return array{
     *     display_name: string|null,
     *     short_name: string|null,
     *     locality: string|null,
     *     district: string|null,
     *     governorate: string|null,
     *     country: string|null,
     *     provider: string,
     *     available: bool
     * }
     */
    public function resolve(float $latitude, float $longitude): array
    {
        $empty = [
            'display_name' => null,
            'short_name' => null,
            'locality' => null,
            'district' => null,
            'governorate' => null,
            'country' => null,
            'provider' => 'nominatim',
            'available' => false,
        ];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return $empty;
        }

        try {
            $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
            $response = Http::withHeaders([
                'User-Agent' => 'Funity/1.0 (https://www.funnity.com; reverse-geocode)',
                'Accept-Language' => $locale === 'ar' ? 'ar,en' : 'en,ar',
            ])
                ->timeout(6)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'jsonv2',
                    'zoom' => 14,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return $empty;
            }

            $json = $response->json();
            $address = is_array($json['address'] ?? null) ? $json['address'] : [];

            $locality = $this->firstFilled($address, [
                'city', 'town', 'village', 'municipality', 'city_district',
            ]);
            $district = $this->firstFilled($address, [
                'suburb', 'neighbourhood', 'quarter', 'district', 'city_district',
            ]);
            $governorate = $this->firstFilled($address, [
                'state', 'province', 'region', 'county',
            ]);
            $country = $this->firstFilled($address, ['country']);

            $short = $locality ?? $district ?? $governorate;
            $displayParts = array_values(array_filter([
                $district && $district !== $locality ? $district : null,
                $locality,
                $governorate,
                $country,
            ]));
            $display = $displayParts !== []
                ? implode(', ', $displayParts)
                : (filled($json['display_name'] ?? null)
                    ? implode(', ', array_slice(array_map('trim', explode(',', (string) $json['display_name'])), 0, 3))
                    : null);

            if ($short === null && $display !== null) {
                $short = explode(',', $display)[0] ?? $display;
            }

            return [
                'display_name' => $display,
                'short_name' => $short !== null ? trim((string) $short) : null,
                'locality' => $locality,
                'district' => $district,
                'governorate' => $governorate,
                'country' => $country,
                'provider' => 'nominatim',
                'available' => $display !== null || $short !== null,
            ];
        } catch (\Throwable $e) {
            Log::warning('location.reverse_failed', ['error' => $e->getMessage()]);

            return $empty;
        }
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  list<string>  $keys
     */
    private function firstFilled(array $address, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($address[$key] ?? null)) {
                return trim((string) $address[$key]);
            }
        }

        return null;
    }
}
