<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Events\DiscoverNearbyEventsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(Request $request, DiscoverNearbyEventsService $discover): JsonResponse
    {
        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 2) {
            return response()->json([
                'data' => [],
                'meta' => ['q' => $q, 'type' => 'events'],
            ]);
        }

        $events = $discover->discover($request->user(), [
            'q' => $q,
            'per_page' => 8,
        ]);

        $data = $events->getCollection()->map(fn ($event) => [
            'id' => $event->id,
            'title' => $event->title,
            'subtitle' => collect([
                $event->city?->localizedName(),
                $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A'),
            ])->filter()->implode(' · '),
            'meta' => $event->category?->localizedName(),
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'q' => $q,
                'type' => 'events',
                'total' => $events->total(),
            ],
        ]);
    }
}
