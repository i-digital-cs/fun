<?php

namespace App\Http\Controllers\Api\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Chat\ReactToEventMessageRequest;
use App\Http\Requests\Events\Chat\StoreEventMessageRequest;
use App\Http\Resources\EventMessageResource;
use App\Models\Event;
use App\Models\EventMessage;
use App\Services\Events\Chat\ListEventMessagesService;
use App\Services\Events\Chat\ListMessageReactionsService;
use App\Services\Events\Chat\MarkEventChatReadService;
use App\Services\Events\Chat\PostEventMessageService;
use App\Services\Events\Chat\ReactToEventMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventChatController extends Controller
{
    public function index(
        Request $request,
        Event $event,
        ListEventMessagesService $service,
        MarkEventChatReadService $readService,
    ): AnonymousResourceCollection|JsonResponse {
        $this->authorize('chat', $event);

        try {
            $messages = $service->list(
                $request->user(),
                $event,
                (int) $request->integer('per_page', 50)
            );
            $readService->mark($request->user(), $event);
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

        $message->setAttribute('likes_count', 0);
        $message->setAttribute('dislikes_count', 0);
        $message->setAttribute('my_reaction', null);

        return (new EventMessageResource($message))->response()->setStatusCode(201);
    }

    public function react(
        ReactToEventMessageRequest $request,
        Event $event,
        EventMessage $message,
        ReactToEventMessageService $service,
    ): JsonResponse {
        $this->authorize('chat', $event);

        try {
            $result = $service->react(
                $request->user(),
                $event,
                $message,
                $request->input('type'),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $result['message']->id,
                'event_id' => $result['message']->event_id,
                'likes_count' => $result['likes_count'],
                'dislikes_count' => $result['dislikes_count'],
                'my_reaction' => $result['my_reaction'],
            ],
        ]);
    }

    public function reactions(
        Request $request,
        Event $event,
        EventMessage $message,
        ListMessageReactionsService $service,
    ): JsonResponse {
        $this->authorize('chat', $event);

        try {
            $lists = $service->list($request->user(), $event, $message);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['data' => $lists]);
    }
}
