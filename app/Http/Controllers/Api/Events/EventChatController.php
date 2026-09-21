<?php

namespace App\Http\Controllers\Api\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Chat\StoreEventMessageRequest;
use App\Http\Resources\EventMessageResource;
use App\Models\Event;
use App\Services\Events\Chat\ListEventMessagesService;
use App\Services\Events\Chat\PostEventMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventChatController extends Controller
{
    public function index(Request $request, Event $event, ListEventMessagesService $service): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('chat', $event);

        try {
            $messages = $service->list(
                $request->user(),
                $event,
                (int) $request->integer('per_page', 50)
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return EventMessageResource::collection($messages);
    }

    public function store(
        StoreEventMessageRequest $request,
        Event $event,
        PostEventMessageService $service,
    ): JsonResponse {
        $this->authorize('chat', $event);

        try {
            $message = $service->post($request->user(), $event, $request->string('body')->toString());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new EventMessageResource($message))->response()->setStatusCode(201);
    }
}
