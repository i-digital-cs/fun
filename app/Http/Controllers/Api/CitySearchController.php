<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\Location\SearchCitiesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitySearchController extends Controller
{
    public function __invoke(Request $request, SearchCitiesService $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $cities = $search->search(
            (string) ($validated['q'] ?? ''),
            (int) ($validated['limit'] ?? 12),
        );

        return response()->json([
            'data' => $cities->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $city->localizedName(),
                'name_en' => $city->name_en,
                'name_ar' => $city->name_ar,
                'slug' => $city->slug,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'subtitle' => app()->getLocale() === 'ar'
                    ? 'مصر'
                    : 'Egypt',
                'source' => 'city',
            ])->values(),
            'distances_km' => config('location.distances_km'),
            'default_distance_km' => config('location.default_distance_km'),
        ]);
    }
}
