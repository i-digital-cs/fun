<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Events\Chat\ListUserChatsService;
use App\Services\Events\Chat\MarkEventChatReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatInboxController extends Controller
{
    public function index(Request $request, ListUserChatsService $service): JsonResponse
    {
        $user = $request->user();
        $chats = $service->list($user);

        return response()->json([
            'data' => $chats->values(),
            'meta' => [
                'unread_total' => (int) $chats->sum('unread_count'),
            ],
        ]);
    }

    public function markRead(
        Request $request,
        Event $event,
        MarkEventChatReadService $service,
    ): JsonResponse {
        $this->authorize('chat', $event);

        $read = $service->mark($request->user(), $event);

        return response()->json([
            'data' => [
                'event_id' => $event->id,
                'last_read_message_id' => $read->last_read_message_id,
                'last_read_at' => $read->last_read_at?->toIso8601String(),
            ],
        ]);
    }
}
