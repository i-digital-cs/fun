<?php

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\DiscoverEventsRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\Events\EventDiscoveryService;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ToggleEventFavoriteService;
use Illuminate\Http\JsonResponse;

class EventDiscoveryController extends Controller
{
    public function home(
        DiscoverEventsRequest $request,
        EventDiscoveryService $discovery,
        ResolveEventJoinActionService $joinActions,
        ToggleEventFavoriteService $favorites,
    ): JsonResponse {
        $user = $request->user('sanctum');
        $bundle = $discovery->homeBundle($user, $request->filters());

        $all = collect($bundle['nearby'])
            ->concat($bundle['worth_the_trip'])
            ->unique('id')
            ->values();

        $actions = $joinActions->resolveMany($user, $all);
        $favorited = $user ? $favorites->favoritedIdsFor($user, $all) : [];

        $decorate = function (array $events) use ($actions, $favorited): array {
            return collect($events)->map(function (Event $event) use ($actions, $favorited) {
                $event->setAttribute('join_action', $actions[$event->id]->value ?? null);
                $event->setAttribute('is_favorited', isset($favorited[$event->id]));

                return (new EventResource($event))->resolve();
            })->all();
        };

        return response()->json([
            'meta' => $bundle['meta'],
            'nearby' => $decorate($bundle['nearby']),
            'worth_the_trip' => $decorate($bundle['worth_the_trip']),
        ]);
    }
}
