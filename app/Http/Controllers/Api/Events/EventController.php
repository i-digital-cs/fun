<?php

namespace App\Http\Controllers\Api\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\DiscoverEventsRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\Events\CreateEventService;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ResolveEventJoinActionService;
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
    ): AnonymousResourceCollection {
        $events = $discover->discover($request->user(), $request->filters());
        $actions = $joinActions->resolveMany($request->user(), $events->getCollection());

        $events->getCollection()->transform(function (Event $event) use ($actions) {
            $event->setAttribute('join_action', $actions[$event->id]->value ?? null);

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

    public function show(Event $event, ResolveEventJoinActionService $joinActionService): JsonResponse
    {
        $this->authorize('view', $event);

        $event->load(['category', 'city'])->loadCount('members');

        return response()->json([
            'data' => (new EventResource($event))->resolve(),
            'join_action' => $joinActionService->resolve(auth()->user(), $event)->value,
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
