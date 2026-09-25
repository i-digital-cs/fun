<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationInboxController extends Controller
{
    public function index(Request $request, NotificationInboxService $inbox): JsonResponse
    {
        $user = $request->user();
        $page = $inbox->paginate($user, min(50, max(1, (int) $request->integer('per_page', 20))));

        return response()->json([
            'data' => collect($page->items())->map(fn ($n) => $this->transform($n))->values(),
            'meta' => [
                'unread_count' => $inbox->unreadCount($user),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request, NotificationInboxService $inbox): JsonResponse
    {
        return response()->json([
            'data' => [
                'unread_count' => $inbox->unreadCount($request->user()),
            ],
        ]);
    }

    public function markRead(Request $request, string $notification, NotificationInboxService $inbox): JsonResponse
    {
        $item = $inbox->markRead($request->user(), $notification);
        if ($item === null) {
            abort(404);
        }

        return response()->json(['data' => $this->transform($item)]);
    }

    public function markAllRead(Request $request, NotificationInboxService $inbox): JsonResponse
    {
        $updated = $inbox->markAllRead($request->user());

        return response()->json([
            'data' => [
                'marked' => $updated,
                'unread_count' => 0,
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Notifications\DatabaseNotification  $notification
     * @return array<string, mixed>
     */
    private function transform(object $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? class_basename($notification->type),
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? null,
            'api_path' => $data['api_path'] ?? null,
            'event_id' => $data['event_id'] ?? null,
            'join_request_id' => $data['join_request_id'] ?? null,
            'data' => $data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
