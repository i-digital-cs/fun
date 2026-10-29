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
            $options = [
                'client_uuid' => $request->input('client_uuid'),
                'reply_to_id' => $request->integer('reply_to_id') ?: null,
            ];
            if ($request->hasFile('media')) {
                $media = app(\App\Services\Chat\ChatMediaService::class);
                $kind = (string) $request->input('media_kind', 'file');
                $stored = match ($kind) {
                    'voice' => $media->storeVoice($request->file('media'), (int) $request->user()->id, $request->integer('duration_ms') ?: null),
                    'image' => $media->storeImage($request->file('media'), (int) $request->user()->id),
                    default => $media->storeFile($request->file('media'), (int) $request->user()->id),
                };
                $options = array_merge($options, [
                    'message_type' => $stored['message_type'],
                    'media_path' => $stored['path'],
                    'media_mime' => $stored['mime'],
                    'media_bytes' => $stored['bytes'],
                    'media_duration_ms' => $stored['duration_ms'],
                ]);
            }
            $message = $service->post(
                $request->user(),
                $event,
                (string) $request->input('body', ''),
                $options
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $message->setAttribute('likes_count', 0);
        $message->setAttribute('dislikes_count', 0);
        $message->setAttribute('my_reaction', null);
        $message->loadMissing('replyTo.user:id,name');

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
                $request->input('emoji'),
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
                'emoji_counts' => $result['emoji_counts'],
                'my_emojis' => $result['my_emojis'],
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
