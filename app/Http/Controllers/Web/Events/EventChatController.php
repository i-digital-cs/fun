<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Chat\ReactToEventMessageRequest;
use App\Http\Requests\Events\Chat\StoreEventMessageRequest;
use App\Http\Resources\EventMessageResource;
use App\Models\Event;
use App\Models\EventMessage;
use App\Services\Events\Chat\ListMessageReactionsService;
use App\Services\Events\Chat\PostEventMessageService;
use App\Services\Events\Chat\ReactToEventMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EventChatController extends Controller
{
    /**
     * Legacy /events/{id}/{slug}/chat → canonical Chat V2 workspace.
     * Authorization runs here so unauthorized users never land on /chats.
     */
    public function show(Event $event, string $slug): RedirectResponse
    {
        $this->authorize('chat', $event);

        return redirect()->to(chat_event_url($event));
    }

    public function store(
        StoreEventMessageRequest $request,
        Event $event,
        string $slug,
        PostEventMessageService $service,
    ): RedirectResponse|JsonResponse {
        $this->authorize('chat', $event);

        try {
            $options = [
                'client_uuid' => $request->input('client_uuid'),
                'reply_to_id' => $request->integer('reply_to_id') ?: null,
            ];
            if ($request->hasFile('media')) {
                $media = app(\App\Services\Chat\ChatMediaService::class);
                $kind = (string) $request->input('media_kind', 'file');
                $scopeId = (int) $event->id;
                $stored = match ($kind) {
                    'voice' => $media->storeVoice(
                        $request->file('media'),
                        (int) $request->user()->id,
                        $request->integer('duration_ms') ?: null,
                        $scopeId,
                        'event',
                    ),
                    'image' => $media->storeImage($request->file('media'), (int) $request->user()->id, $scopeId, 'event'),
                    'video' => $media->storeVideo($request->file('media'), (int) $request->user()->id, $scopeId, 'event'),
                    default => $media->storeFile($request->file('media'), (int) $request->user()->id, $scopeId, 'event'),
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
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['body' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            $message->loadMissing(['user:id,name,public_id,profile_image_path', 'replyTo.user:id,name']);

            return (new EventMessageResource($message))->response()->setStatusCode(201);
        }

        return redirect()->to(chat_event_url($event));
    }

    public function react(
        ReactToEventMessageRequest $request,
        Event $event,
        string $slug,
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
        Event $event,
        string $slug,
        EventMessage $message,
        ListMessageReactionsService $service,
    ): JsonResponse {
        $this->authorize('chat', $event);

        try {
            $lists = $service->list(request()->user(), $event, $message);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['data' => $lists]);
    }
}
