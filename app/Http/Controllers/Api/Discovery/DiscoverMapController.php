<?php

namespace App\Http\Controllers\Api\Discovery;

use App\Http\Controllers\Controller;
use App\Services\Events\DiscoverMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoverMapController extends Controller
{
    public function __invoke(Request $request, DiscoverMapService $map): JsonResponse
    {
        $validated = $request->validate([
            'north' => ['required', 'numeric', 'between:-90,90'],
            'south' => ['required', 'numeric', 'between:-90,90'],
            'east' => ['required', 'numeric', 'between:-180,180'],
            'west' => ['required', 'numeric', 'between:-180,180'],
            'zoom' => ['sometimes', 'nullable', 'integer', 'min:4', 'max:18'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'when' => ['sometimes', 'nullable', 'string', 'in:today,tomorrow,weekend'],
            'include_approximate' => ['sometimes', 'nullable', 'boolean'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:250'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'distance_km' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:500'],
            'discovery_mode' => ['sometimes', 'nullable', 'string', 'in:device,manual,nationwide'],
        ]);

        $payload = $map->mapPayload($request->user(), $validated);

        return response()->json($payload);
    }
}
