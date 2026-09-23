<?php

namespace App\Http\Controllers\Api\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\DiscoverEventsRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventRating;
use App\Services\Events\CreateEventService;
use App\Services\Events\CreatorRatingSummaryService;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ToggleEventFavoriteService;
use App\Services\Events\TransitionEventStatusService;
use App\Services\Events\UpdateEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(
        DiscoverEventsRequest $request,
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
        ToggleEventFavoriteService $favorites,
    ): AnonymousResourceCollection {
        $user = $request->user('sanctum');
        $events = $discover->discover($user, $request->filters());
        $actions = $joinActions->resolveMany($user, $events->getCollection());
        $favorited = $user
            ? $favorites->favoritedIdsFor($user, $events->getCollection())
            : [];

        $events->getCollection()->transform(function (Event $event) use ($actions, $favorited) {
            $event->setAttribute('join_action', $actions[$event->id]->value ?? null);
            $event->setAttribute('is_favorited', isset($favorited[$event->id]));

            return $event;
        });

        return EventResource::collection($events);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        $events = Event::query()
            ->with(['category', 'city'])
            ->withCount('members')
            ->whereHas('members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('starts_at')
            ->paginate(12);

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request, CreateEventService $service): JsonResponse
    {
        $this->authorize('create', Event::class);

        try {
            $event = $service->create(
                $request->user(),
                $request->safe()->except('image'),
                $request->file('image')
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new EventResource($event->loadCount('members')))->response()->setStatusCode(201);
    }

    public function show(
        Event $event,
        ResolveEventJoinActionService $joinActionService,
        CreatorRatingSummaryService $ratingSummary,
        ToggleEventFavoriteService $favorites,
    ): JsonResponse {
        $this->authorize('view', $event);

        $event->load(['category', 'city', 'creator:id,public_id,name,profile_image_path'])->loadCount('members');

        $user = auth('sanctum')->user();
        $userRating = null;

        if ($user !== null) {
            $userRating = EventRating::query()
                ->where('event_id', $event->id)
                ->where('rater_id', $user->id)
                ->first();
            $event->setAttribute('is_favorited', $favorites->isFavorited($user, $event));
        }

        $creatorSummary = $event->creator
            ? $ratingSummary->forUser($event->creator)
            : ['average' => null, 'count' => 0];

        $resource = (new EventResource($event))->resolve();

        return response()->json([
            'data' => $resource,
            'join_action' => $joinActionService->resolve($user, $event)->value,
            'ready_for_ratings' => $event->isReadyForRatings(),
            'can_rate' => $user !== null && $user->can('rate', $event),
            'my_rating' => $userRating
                ? (new \App\Http\Resources\EventRatingResource($userRating))->resolve()
                : null,
            'creator_rating' => $creatorSummary,
            'creator' => $resource['creator'] ?? null,
            'is_favorited' => (bool) ($event->is_favorited ?? false),
        ]);
    }

    public function update(
        UpdateEventRequest $request,
        Event $event,
        UpdateEventService $service,
    ): EventResource|JsonResponse {
        $this->authorize('update', $event);

        try {
            $event = $service->update(
                $request->user(),
                $event,
                $request->safe()->except('image'),
                $request->file('image')
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new EventResource($event->loadCount('members'));
    }

    public function publish(Event $event, TransitionEventStatusService $service): EventResource|JsonResponse
    {
        $this->authorize('publish', $event);

        try {
            $event = $service->publish(request()->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new EventResource($event->loadCount('members'));
    }

    public function cancel(Event $event, TransitionEventStatusService $service): EventResource|JsonResponse
    {
        $this->authorize('cancel', $event);

        try {
            $event = $service->cancel(request()->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new EventResource($event->loadCount('members'));
    }

    public function complete(Event $event, TransitionEventStatusService $service): EventResource|JsonResponse
    {
        $this->authorize('complete', $event);

        try {
            $event = $service->complete(request()->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new EventResource($event->loadCount('members'));
    }
}
