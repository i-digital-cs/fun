<?php

namespace App\Http\Controllers\Api;

use App\Support\MapsUrlParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResolveMapsUrlController
{
    public function __invoke(Request $request, MapsUrlParser $parser): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:500'],
        ]);

        $parsed = $parser->parse($validated['url']);

        if ($parsed === null) {
            return response()->json([
                'message' => __('events.maps_url_unresolved'),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'data' => [
                'latitude' => $parsed['latitude'],
                'longitude' => $parsed['longitude'],
                'maps_url' => $parsed['maps_url'],
                'maps_open_url' => $parser->googleMapsOpenUrl(
                    $parsed['latitude'],
                    $parsed['longitude']
                ),
            ],
        ]);
    }
}
