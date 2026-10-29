<?php

namespace App\Http\Controllers\Api\Discovery;

use App\Http\Controllers\Controller;
use App\Services\Location\ResolveLocationLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReverseGeocodeController extends Controller
{
    public function __invoke(Request $request, ResolveLocationLabelService $labels): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $resolved = $labels->resolve(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
        );

        return response()->json([
            'data' => $resolved,
            // Coordinates echoed only as confirmation — clients already hold them.
            'meta' => [
                'source' => 'device_or_manual_coords',
                'note' => 'GPS accuracy is independent of reverse-geocode precision.',
            ],
        ]);
    }
}
