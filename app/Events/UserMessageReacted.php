<?php

namespace App\Events;

use App\Models\Event;
use App\Models\EventMessage;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMessageReacted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Event $event,
        public EventMessage $message,
        public User $owner,
        public User $actor,
        public string $reaction,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->owner->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.reaction';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $title = $this->reaction === 'like'
            ? __('chat.notify_liked_title')
            : __('chat.notify_disliked_title');
        $body = $this->reaction === 'like'
            ? __('chat.notify_liked_body', ['name' => $this->actor->name, 'event' => $this->event->title])
            : __('chat.notify_disliked_body', ['name' => $this->actor->name, 'event' => $this->event->title]);

        return [
            'type' => 'message_reacted',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_url' => event_show_url($this->event),
            'chat_url' => event_route('events.chat', $this->event),
            'message_id' => $this->message->id,
            'reaction' => $this->reaction,
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'initials' => user_initials($this->actor->name),
                'profile_image_path' => $this->actor->profile_image_path,
            ],
            'title' => $title,
            'body' => $body,
        ];
    }
}
