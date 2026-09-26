<?php

namespace App\Http\Controllers\Api;

use App\Services\Maps\SuggestPlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestPlacesController
{
    public function __invoke(Request $request, SuggestPlacesService $service): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $suggestions = $service->suggest(
            $validated['q'],
            isset($validated['city_id']) ? (int) $validated['city_id'] : null,
            (int) ($validated['limit'] ?? 8),
        );

        return response()->json([
            'data' => $suggestions,
        ]);
    }
}
