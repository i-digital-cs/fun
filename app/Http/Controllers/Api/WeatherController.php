<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\Weather\OpenMeteoWeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function __invoke(Request $request, OpenMeteoWeatherService $weather): JsonResponse
    {
        $locale = $request->query('locale')
            ?: $request->user()?->locale
            ?: app()->getLocale();
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'en';

        $cityId = $request->integer('city_id') ?: $request->user()?->city_id;
        if (! $cityId) {
            return response()->json([
                'message' => __('weather.city_required'),
                'data' => null,
            ], 422);
        }

        $city = City::query()->active()->find($cityId);
        if ($city === null) {
            return response()->json([
                'message' => __('weather.city_unavailable'),
                'data' => null,
            ], 404);
        }

        $payload = $weather->forCity($city, $locale);
        if ($payload === null) {
            return response()->json([
                'message' => __('weather.unavailable'),
                'data' => null,
            ], 503);
        }

        return response()->json(['data' => $payload]);
    }
}
