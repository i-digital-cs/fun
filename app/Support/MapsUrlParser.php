<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Extract lat/lng from Google Maps / OSM share links without any paid API key.
 */
class MapsUrlParser
{
    /**
     * @return array{latitude: float, longitude: float, maps_url: string}|null
     */
    public function parse(string $raw): ?array
    {
        $url = trim($raw);
        if ($url === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $resolved = $this->resolveRedirects($url);
        $coords = $this->extractCoords($resolved) ?? $this->extractCoords($url);

        if ($coords === null) {
            return null;
        }

        return [
            'latitude' => $coords[0],
            'longitude' => $coords[1],
            'maps_url' => $url,
        ];
    }

    public function openStreetMapUrl(float $lat, float $lng, int $zoom = 16): string
    {
        return sprintf(
            'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=%d/%s/%s',
            rawurlencode((string) $lat),
            rawurlencode((string) $lng),
            $zoom,
            rawurlencode((string) $lat),
            rawurlencode((string) $lng),
        );
    }

    public function googleMapsOpenUrl(float $lat, float $lng): string
    {
        // Opening Google Maps in the browser needs no API key.
        return sprintf('https://www.google.com/maps?q=%s,%s', $lat, $lng);
    }

    private function resolveRedirects(string $url): string
    {
        if (! preg_match('#(maps\.app\.goo\.gl|goo\.gl/maps|g\.co/|maps\.google\.[^/]+/maps)#i', $url)) {
            return $url;
        }

        try {
            $response = Http::withOptions([
                'allow_redirects' => false,
                'timeout' => 8,
            ])->withHeaders([
                'User-Agent' => 'FunityMapsParser/1.0',
            ])->get($url);

            $location = $response->header('Location');
            if (filled($location)) {
                if (str_starts_with($location, '/')) {
                    $parts = parse_url($url);
                    $location = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? 'maps.google.com').$location;
                }

                return $location;
            }
        } catch (\Throwable) {
            // Fall back to original URL parsing.
        }

        return $url;
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function extractCoords(string $url): ?array
    {
        $candidates = [
            // https://www.google.com/maps/@30.0444,31.2357,17z
            '/@(-?\d+\.?\d*),(-?\d+\.?\d*)(?:,|\/|$)/',
            // !3d30.0444!4d31.2357
            '/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/',
            // ?q=30.0444,31.2357 or q=loc:30.0444,31.2357
            '/[?&](?:q|query)=(?:loc:)?(-?\d+\.?\d*),(-?\d+\.?\d*)/',
            // ll= or center=
            '/[?&](?:ll|center)=(-?\d+\.?\d*),(-?\d+\.?\d*)/',
            // destination=
            '/[?&]destination=(-?\d+\.?\d*),(-?\d+\.?\d*)/',
            // OSM mlat/mlon
            '/[?&]mlat=(-?\d+\.?\d*).*?[?&]mlon=(-?\d+\.?\d*)/',
            // OSM #map=17/30.0444/31.2357
            '/#map=\d+\/(-?\d+\.?\d*)\/(-?\d+\.?\d*)/',
            // plain pair anywhere
            '/(-?\d{1,2}\.\d{3,}),\s*(-?\d{1,3}\.\d{3,})/',
        ];

        foreach ($candidates as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];
                if ($this->valid($lat, $lng)) {
                    return [$lat, $lng];
                }
            }
        }

        return null;
    }

    private function valid(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
            && ! ($lat == 0.0 && $lng == 0.0);
    }
}
