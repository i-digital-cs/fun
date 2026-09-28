<?php

namespace App\Jobs;

use App\Enums\AccountStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Notifications\FriendEventNotification;
use App\Services\Friends\FriendshipService;
use App\Services\Safety\UserBlockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyFriendsOfPublishedEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $eventId,
        public readonly int $publisherId,
    ) {
    }

    public function handle(
        FriendshipService $friendships,
        UserBlockService $blocks,
    ): void {
        $event = Event::query()->find($this->eventId);
        $publisher = User::query()->find($this->publisherId);

        if ($event === null || $publisher === null) {
            return;
        }

        if ($event->status !== EventStatus::Published) {
            return;
        }

        $friendIds = $friendships->acceptedFriendIds($publisher);
        $blockedIds = $blocks->blockedUserIdsFor($publisher);

        $recipientIds = array_values(array_diff($friendIds, $blockedIds));

        if ($recipientIds === []) {
            return;
        }

        $recipients = User::query()
            ->whereIn('id', $recipientIds)
            ->where('status', AccountStatus::Active)
            ->where('notify_friend_events', true)
            ->get();

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new FriendEventNotification($publisher, $event));
            } catch (Throwable $e) {
                Log::warning('friend_event_notify_failed', [
                    'event_id' => $event->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
