<?php

namespace App\Http\Controllers\Api\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventRatingRequest;
use App\Http\Resources\EventRatingResource;
use App\Models\Event;
use App\Models\EventRating;
use App\Services\Events\RateEventCreatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventRatingController extends Controller
{
    public function index(Event $event): AnonymousResourceCollection
    {
        $this->authorize('view', $event);

        $ratings = EventRating::query()
            ->with(['rater:id,name'])
            ->where('event_id', $event->id)
            ->latest()
            ->paginate(20);

        return EventRatingResource::collection($ratings);
    }

    public function store(
        StoreEventRatingRequest $request,
        Event $event,
        RateEventCreatorService $service,
    ): JsonResponse {
        $this->authorize('rate', $event);

        try {
            $rating = $service->rate(
                $request->user(),
                $event,
                (int) $request->validated('stars'),
                $request->validated('comment'),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new EventRatingResource($rating))->response()->setStatusCode(201);
    }

    public function mine(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $rating = EventRating::query()
            ->with(['rater:id,name', 'ratedUser:id,name'])
            ->where('event_id', $event->id)
            ->where('rater_id', request()->user()->id)
            ->first();

        if ($rating === null) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => (new EventRatingResource($rating))->resolve()]);
    }
}
