<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $cities = City::query()
            ->active()
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'slug', 'latitude', 'longitude']);

        return response()->json([
            'cities' => $cities->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $city->localizedName(),
                'name_en' => $city->name_en,
                'name_ar' => $city->name_ar,
                'slug' => $city->slug,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
            ]),
            'distances_km' => config('location.distances_km'),
            'default_distance_km' => config('location.default_distance_km'),
        ]);
    }
}
