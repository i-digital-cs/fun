<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\EventMessage;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageReactedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Event $event,
        public readonly EventMessage $message,
        public readonly User $actor,
        public readonly string $reaction,
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
        $title = $this->reaction === 'like'
            ? __('chat.notify_liked_title')
            : __('chat.notify_disliked_title');
        $body = $this->reaction === 'like'
            ? __('chat.notify_liked_body', ['name' => $this->actor->name, 'event' => $this->event->title])
            : __('chat.notify_disliked_body', ['name' => $this->actor->name, 'event' => $this->event->title]);

        return [
            'type' => 'message_reacted',
            'title' => $title,
            'body' => $body,
            'event_id' => $this->event->id,
            'event_slug' => $this->event->slug,
            'event_title' => $this->event->title,
            'message_id' => $this->message->id,
            'reaction' => $this->reaction,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => event_route('events.chat', $this->event),
            'api_path' => '/events/'.$this->event->id.'/chat',
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
                'type' => 'message_reacted',
                'title' => (string) $payload['title'],
                'body' => (string) $payload['body'],
                'event_id' => (string) $this->event->id,
                'event_title' => (string) $this->event->title,
                'message_id' => (string) $this->message->id,
                'reaction' => $this->reaction,
                'api_path' => (string) $payload['api_path'],
            ],
        ];
    }
}
