<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ResolveEventJoinActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(
        Request $request,
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
    ): JsonResponse {
        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 2) {
            return response()->json([
                'data' => [],
                'meta' => ['q' => $q, 'type' => 'events'],
            ]);
        }

        // Nationwide text search so joined/owned/pending outside radius still appear.
        $events = $discover->discover($request->user('sanctum'), [
            'q' => $q,
            'per_page' => 8,
            'city_id' => null,
        ]);

        $actions = $joinActions->resolveMany($request->user('sanctum'), $events->getCollection());

        $data = $events->getCollection()->map(function ($event) use ($actions) {
            $action = $actions[$event->id] ?? null;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'subtitle' => collect([
                    $event->city?->localizedName(),
                    $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A'),
                ])->filter()->implode(' · '),
                'meta' => $event->category?->localizedName(),
                'join_action' => $action?->value,
                'badge' => match ($action?->value) {
                    'owned' => __('events.your_own'),
                    'joined' => __('events.joined'),
                    'request_pending' => __('events.request_pending'),
                    default => null,
                },
            ];
        })->values();

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
