<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $publisher,
        public readonly Event $event,
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
            'type' => 'friend_event',
            'title' => __('friends.notify_event_title'),
            'body' => __('friends.notify_event_body', [
                'name' => $this->publisher->name,
                'event' => $this->event->title,
            ]),
            'publisher_id' => $this->publisher->id,
            'publisher_name' => $this->publisher->name,
            'event_id' => $this->event->id,
            'event_slug' => $this->event->slug,
            'url' => event_show_url($this->event),
            'api_path' => '/events/'.$this->event->id,
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
                'type' => 'friend_event',
                'event_id' => (string) $this->event->id,
                'api_path' => (string) $payload['api_path'],
            ],
        ];
    }
}
