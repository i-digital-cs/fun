<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JoinRequestAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Event $event,
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
            'type' => 'join_accepted',
            'title' => __('events.notify_join_accepted_title'),
            'body' => __('events.notify_join_accepted_body', [
                'event' => $this->event->title,
            ]),
            'event_id' => $this->event->id,
            'event_slug' => $this->event->slug,
            'accepter_id' => $this->accepter->id,
            'accepter_name' => $this->accepter->name,
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
                'type' => 'join_accepted',
                'event_id' => (string) $this->event->id,
                'api_path' => (string) $payload['api_path'],
            ],
        ];
    }
}
