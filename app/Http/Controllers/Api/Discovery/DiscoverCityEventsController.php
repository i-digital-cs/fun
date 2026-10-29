<?php

namespace App\Http\Controllers\Api\Discovery;

use App\Http\Controllers\Controller;
use App\Services\Events\DiscoverMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoverCityEventsController extends Controller
{
    public function __invoke(Request $request, DiscoverMapService $map): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'when' => ['sometimes', 'nullable', 'string', 'in:today,tomorrow,weekend'],
        ]);

        $payload = $map->cityEvents(
            $request->user(),
            (int) $validated['city_id'],
            $validated
        );

        return response()->json($payload);
    }
}
