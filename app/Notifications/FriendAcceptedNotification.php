<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FriendAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $accepter,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'friend_accepted',
            'title' => __('friends.notify_accepted_title'),
            'body' => __('friends.notify_accepted_body', ['name' => $this->accepter->name]),
            'accepter_id' => $this->accepter->id,
            'accepter_name' => $this->accepter->name,
            'accepter_public_id' => $this->accepter->public_id,
            'url' => route('friends.index'),
            'api_path' => '/friends',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm(object $notifiable): array
    {
        $payload = $this->toArray($notifiable);

        return [
            'title' => (string) $payload['title'],
            'body' => (string) $payload['body'],
            'data' => [
                'type' => 'friend_accepted',
                'accepter_id' => (string) $this->accepter->id,
                'api_path' => (string) $payload['api_path'],
            ],
        ];
    }
}
