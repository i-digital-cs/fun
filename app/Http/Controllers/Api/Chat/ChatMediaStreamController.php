<?php

namespace App\Http\Controllers\Api\Chat;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Models\Event;
use App\Models\EventMessage;
use App\Services\Chat\ChatMediaService;
use App\Services\Friends\DirectMessageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatMediaStreamController extends Controller
{
    public function direct(
        Request $request,
        DirectMessage $message,
        DirectMessageService $dms,
        ChatMediaService $media,
    ): StreamedResponse|Response {
        $conversation = DirectConversation::query()->findOrFail($message->conversation_id);
        try {
            $dms->assertParticipant($request->user(), $conversation);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        abort_unless(filled($message->media_path), 404);

        return $media->stream(
            (string) $message->media_path,
            (string) ($message->media_mime ?: 'application/octet-stream'),
            $request
        );
    }

    public function event(
        Request $request,
        Event $event,
        EventMessage $message,
        ChatMediaService $media,
    ): StreamedResponse|Response {
        $this->authorize('chat', $event);
        abort_unless((int) $message->event_id === (int) $event->id, 404);
        abort_unless(filled($message->media_path), 404);

        return $media->stream(
            (string) $message->media_path,
            (string) ($message->media_mime ?: 'application/octet-stream'),
            $request
        );
    }
}
