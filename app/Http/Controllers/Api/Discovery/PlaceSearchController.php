<?php

namespace App\Http\Controllers\Api\Discovery;

use App\Http\Controllers\Controller;
use App\Services\Location\SearchCitiesService;
use App\Services\Maps\SuggestPlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Discovery place search: local cities first, then Photon/Nominatim places.
 * No paid geocoder. Debounce client-side; throttle server-side.
 */
class PlaceSearchController extends Controller
{
    public function __invoke(
        Request $request,
        SearchCitiesService $cities,
        SuggestPlacesService $places,
    ): JsonResponse {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:15'],
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $q = trim($validated['q']);

        $cityRows = $cities->search($q, min(8, $limit))->map(fn ($city) => [
            'id' => 'city:'.$city->id,
            'source' => 'city',
            'city_id' => $city->id,
            'name' => $city->localizedName(),
            'subtitle' => app()->getLocale() === 'ar' ? 'مصر' : 'Egypt',
            'latitude' => (float) $city->latitude,
            'longitude' => (float) $city->longitude,
            'provider_ref' => null,
        ]);

        $remaining = max(0, $limit - $cityRows->count());
        $external = [];
        if ($remaining > 0) {
            foreach ($places->suggest($q, null, $remaining) as $hit) {
                $external[] = [
                    'id' => 'place:'.md5($hit['label'].'|'.$hit['latitude'].'|'.$hit['longitude']),
                    'source' => 'place',
                    'city_id' => null,
                    'name' => $hit['label'],
                    'subtitle' => app()->getLocale() === 'ar' ? 'مكان' : 'Place',
                    'latitude' => $hit['latitude'],
                    'longitude' => $hit['longitude'],
                    'provider_ref' => null,
                ];
            }
        }

        // Deduplicate near-identical labels.
        $seen = [];
        $data = [];
        foreach ([...$cityRows->all(), ...$external] as $row) {
            $key = mb_strtolower($row['name']).'|'.round($row['latitude'], 3).'|'.round($row['longitude'], 3);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $data[] = $row;
            if (count($data) >= $limit) {
                break;
            }
        }

        return response()->json([
            'data' => $data,
            'provider' => 'cities+photon',
            'attribution' => '© OpenStreetMap contributors · Photon',
        ]);
    }
}
