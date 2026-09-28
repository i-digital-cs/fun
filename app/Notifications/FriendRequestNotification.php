<?php

namespace App\Notifications;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $requester,
        public readonly Friendship $friendship,
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
            'type' => 'friend_request',
            'title' => __('friends.notify_request_title'),
            'body' => __('friends.notify_request_body', ['name' => $this->requester->name]),
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_public_id' => $this->requester->public_id,
            'friendship_id' => $this->friendship->id,
            'url' => route('friends.index', ['tab' => 'requests']),
            'api_path' => '/friends/requests',
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
                'type' => 'friend_request',
                'requester_id' => (string) $this->requester->id,
                'api_path' => (string) $payload['api_path'],
            ],
        ];
    }
}
