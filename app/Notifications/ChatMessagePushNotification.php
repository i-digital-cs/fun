<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\EventMessage;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChatMessagePushNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Event $event,
        public readonly EventMessage $message,
        public readonly User $sender,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Outside-app push only. In-app uses Pusher toast; do not fill the inbox.
        return [FcmChannel::class];
    }

    /**
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->event->title,
            // No message body (privacy / SEC-PRIV-002 parity with Flutter toasts).
            'body' => __('chat.notify_new_message', ['name' => $this->sender->name]),
            'data' => [
                'type' => 'chat_message',
                'event_id' => (string) $this->event->id,
                'message_id' => (string) $this->message->id,
                'api_path' => '/events/'.$this->event->id.'/messages',
            ],
        ];
    }
}
