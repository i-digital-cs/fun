<?php

namespace App\Services\Weather;

use App\Models\City;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoWeatherService
{
    /**
     * @return array<string, mixed>|null
     */
    public function forCity(City $city, ?string $locale = null): ?array
    {
        if ($city->latitude === null || $city->longitude === null) {
            return null;
        }

        $locale = $locale ?: app()->getLocale();
        $cacheKey = sprintf('weather.city.%d.%s', $city->id, $locale === 'ar' ? 'ar' : 'en');
        $ttl = max(5, (int) config('services.weather.cache_minutes', 30));

        return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($city, $locale) {
            return $this->fetch($city, $locale);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetch(City $city, string $locale): ?array
    {
        $days = max(2, min(7, (int) config('services.weather.forecast_days', 5)));
        $url = (string) config('services.weather.base_url', 'https://api.open-meteo.com/v1/forecast');

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get($url, [
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'current' => 'temperature_2m,weather_code',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                    'timezone' => 'auto',
                    'forecast_days' => $days,
                ]);
        } catch (\Throwable $e) {
            Log::warning('weather.fetch_failed', [
                'city_id' => $city->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('weather.http_error', [
                'city_id' => $city->id,
                'status' => $response->status(),
            ]);

            return null;
        }

        $json = $response->json();
        if (! is_array($json) || ! isset($json['current'], $json['daily'])) {
            return null;
        }

        $timezone = (string) ($json['timezone'] ?? config('app.timezone'));
        $now = Carbon::now($timezone);

        $currentCode = (int) ($json['current']['weather_code'] ?? 0);
        $currentTemp = (float) ($json['current']['temperature_2m'] ?? 0);
        $condition = $this->conditionFromCode($currentCode);

        $dailyTimes = $json['daily']['time'] ?? [];
        $dailyCodes = $json['daily']['weather_code'] ?? [];
        $dailyMax = $json['daily']['temperature_2m_max'] ?? [];
        $dailyMin = $json['daily']['temperature_2m_min'] ?? [];

        $high = isset($dailyMax[0]) ? (int) round((float) $dailyMax[0]) : (int) round($currentTemp);
        $low = isset($dailyMin[0]) ? (int) round((float) $dailyMin[0]) : (int) round($currentTemp);

        $forecast = [];
        $count = is_array($dailyTimes) ? count($dailyTimes) : 0;
        for ($i = 1; $i < $count && count($forecast) < 4; $i++) {
            $day = Carbon::parse((string) $dailyTimes[$i], $timezone);
            $code = (int) ($dailyCodes[$i] ?? 0);
            $dayCondition = $this->conditionFromCode($code);
            $forecast[] = [
                'date' => $day->toDateString(),
                'day' => $day->locale($locale)->shortDayName,
                'day_key' => strtolower($day->englishDayOfWeek),
                'weather_code' => $code,
                'condition' => $dayCondition['key'],
                'label' => $this->label($dayCondition['key'], $locale),
                'icon' => $dayCondition['icon'],
                'high' => isset($dailyMax[$i]) ? (int) round((float) $dailyMax[$i]) : null,
                'low' => isset($dailyMin[$i]) ? (int) round((float) $dailyMin[$i]) : null,
            ];
        }

        return [
            'provider' => 'open_meteo',
            'city' => [
                'id' => $city->id,
                'name' => $city->localizedName($locale),
                'name_en' => $city->name_en,
                'name_ar' => $city->name_ar,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
            ],
            'timezone' => $timezone,
            'observed_at' => isset($json['current']['time'])
                ? Carbon::parse((string) $json['current']['time'], $timezone)->toIso8601String()
                : $now->toIso8601String(),
            'local_time' => $now->format('H:i'),
            'local_date' => strtoupper($now->locale($locale)->shortDayName).' '.$now->format('m-d'),
            'temperature' => (int) round($currentTemp),
            'high' => $high,
            'low' => $low,
            'range' => $high.'°/'.$low.'°',
            'weather_code' => $currentCode,
            'condition' => $condition['key'],
            'label' => $this->label($condition['key'], $locale),
            'icon' => $condition['icon'],
            'theme' => $condition['theme'],
            'forecast' => $forecast,
        ];
    }

    /**
     * @return array{key: string, icon: string, theme: string}
     */
    private function conditionFromCode(int $code): array
    {
        return match (true) {
            $code === 0 => ['key' => 'clear', 'icon' => 'sun', 'theme' => 'sunny'],
            $code <= 2 => ['key' => 'partly_cloudy', 'icon' => 'sun', 'theme' => 'sunny'],
            $code === 3 => ['key' => 'overcast', 'icon' => 'cloud', 'theme' => 'cloudy'],
            $code === 45, $code === 48 => ['key' => 'fog', 'icon' => 'cloud', 'theme' => 'cloudy'],
            $code >= 51 && $code <= 67 => ['key' => 'rain', 'icon' => 'rain', 'theme' => 'rain'],
            $code >= 71 && $code <= 77 => ['key' => 'snow', 'icon' => 'cloud', 'theme' => 'cloudy'],
            $code >= 80 && $code <= 82 => ['key' => 'showers', 'icon' => 'rain', 'theme' => 'rain'],
            $code >= 95 => ['key' => 'thunder', 'icon' => 'rain', 'theme' => 'rain'],
            default => ['key' => 'cloudy', 'icon' => 'cloud', 'theme' => 'cloudy'],
        };
    }

    private function label(string $key, string $locale): string
    {
        $line = __('weather.'.$key, [], $locale);

        return is_string($line) ? $line : $key;
    }
}
