<?php

namespace App\Services\Chat;

use App\Models\User;
use App\Services\Events\Chat\ListUserChatsService;
use App\Services\Friends\DirectMessageService;
use Illuminate\Support\Collection;

/**
 * Merge event chats + DMs into one conversation-first inbox for Chat V2.
 */
class BuildUnifiedInboxService
{
    public function __construct(
        private readonly ListUserChatsService $eventChats,
        private readonly DirectMessageService $dms,
    ) {}

    /**
     * @return array{
     *     items: Collection<int, array<string, mixed>>,
     *     event_unread: int,
     *     dm_unread: int,
     *     total_unread: int
     * }
     */
    public function build(User $user): array
    {
        $events = $this->eventChats->list($user)->map(function (array $row) {
            $last = $row['last_message'] ?? null;
            $preview = (string) ($last['body'] ?? __('chat.inbox_no_messages'));
            $who = $last
                ? (($last['is_mine'] ?? false)
                    ? __('chat.you').': '
                    : (($last['user']['name'] ?? '').': '))
                : '';

            return [
                'key' => 'event-'.(int) $row['event_id'],
                'type' => 'event',
                'id' => (int) $row['event_id'],
                'title' => (string) ($row['title'] ?? ''),
                'subtitle' => (string) ($row['city'] ?? ''),
                'image_url' => $row['image_url'] ?? null,
                'preview' => $who.$preview,
                'preview_is_mine' => (bool) ($last['is_mine'] ?? false),
                'at' => $last['created_at'] ?? ($row['starts_at'] ?? null),
                'unread_count' => (int) ($row['unread_count'] ?? 0),
                'url' => route('chats.index', ['c' => 'event-'.$row['event_id']]),
                'deep_url' => $row['chat_url'] ?? null,
                'search' => mb_strtolower(($row['title'] ?? '').' '.($row['city'] ?? '').' '.$preview),
            ];
        });

        $direct = $this->dms->listConversations($user)->map(function (array $row) {
            $other = $row['other_user'] ?? [];
            $last = $row['last_message'] ?? null;

            return [
                'key' => 'dm-'.(int) $row['id'],
                'type' => 'direct',
                'id' => (int) $row['id'],
                'title' => (string) ($other['name'] ?? ''),
                'subtitle' => '',
                'image_url' => $other['profile_image_path'] ?? null,
                'initials' => $other['initials'] ?? '?',
                'preview' => (string) ($last['body'] ?? __('chat.inbox_no_messages')),
                'preview_is_mine' => isset($last['user_id']) && (int) $last['user_id'] === (int) auth()->id(),
                'at' => $last['created_at'] ?? $row['last_message_at'] ?? null,
                'unread_count' => (int) ($row['unread_count'] ?? 0),
                'url' => route('chats.index', ['c' => 'dm-'.$row['id']]),
                'deep_url' => $row['url'] ?? null,
                'other_user' => $other,
                'search' => mb_strtolower(($other['name'] ?? '').' '.($last['body'] ?? '')),
            ];
        });

        $items = $events->concat($direct)->sortByDesc(function (array $row) {
            $at = $row['at'] ?? null;

            return $at ? strtotime((string) $at) : 0;
        })->values();

        $eventUnread = $this->eventChats->unreadTotal($user);
        $dmUnread = (int) $direct->sum('unread_count');

        return [
            'items' => $items,
            'event_unread' => $eventUnread,
            'dm_unread' => $dmUnread,
            'total_unread' => $eventUnread + $dmUnread,
        ];
    }
}
