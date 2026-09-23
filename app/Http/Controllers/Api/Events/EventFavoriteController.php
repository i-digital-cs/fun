<?php

namespace App\Http\Controllers\Api\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventFavorite;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ToggleEventFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventFavoriteController extends Controller
{
    public function index(
        Request $request,
        ResolveEventJoinActionService $joinActions,
        ToggleEventFavoriteService $favorites,
    ): JsonResponse {
        $user = $request->user();

        $events = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->whereIn('id', EventFavorite::query()
                ->where('user_id', $user->id)
                ->select('event_id'))
            ->latest('starts_at')
            ->paginate(12);

        $joinById = $joinActions->resolveMany($user, $events->getCollection());
        $events->getCollection()->each(function (Event $event) use ($joinById) {
            $event->join_action = $joinById[$event->id]?->value ?? null;
            $event->is_favorited = true;
        });

        return EventResource::collection($events)->response();
    }

    public function toggle(
        Request $request,
        Event $event,
        ToggleEventFavoriteService $service,
    ): JsonResponse {
        $this->authorize('view', $event);

        try {
            $favorited = $service->toggle($request->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $favorited ? __('events.favorited') : __('events.unfavorited'),
            'is_favorited' => $favorited,
            'event_id' => $event->id,
        ]);
    }
}
