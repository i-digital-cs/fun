<?php

namespace App\Notifications;

use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DirectMessagePushNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly DirectConversation $conversation,
        public readonly DirectMessage $message,
        public readonly User $sender,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('friends.dm_notify_title'),
            // No message body (privacy / SEC-PRIV-002).
            'body' => __('friends.dm_notify_body', ['name' => $this->sender->name]),
            'data' => [
                'type' => 'dm_message',
                'conversation_id' => (string) $this->conversation->id,
                'message_id' => (string) $this->message->id,
                'api_path' => '/dms/'.$this->conversation->id.'/messages',
            ],
        ];
    }
}
