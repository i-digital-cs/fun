<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $requester,
        public readonly Event $event,
        public readonly EventJoinRequest $joinRequest,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Internal inbox + email (no FCM). Push is reserved for chat messages.
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = app(SiteSettingsService::class);
        $siteName = $site->displayName();

        $mail = (new MailMessage)
            ->subject(__('events.mail_join_request_subject', [
                'app' => $siteName,
                'event' => $this->event->title,
            ]))
            ->greeting(__('events.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('events.mail_join_request_body', [
                'requester' => $this->requester->name,
                'event' => $this->event->title,
            ]));

        if (filled($this->joinRequest->message)) {
            $mail->line('> '.$this->joinRequest->message);
        }

        return $mail
            ->action(__('events.mail_join_request_cta'), event_route('events.requests', $this->event))
            ->salutation($siteName);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'join_request',
            'title' => __('events.notify_join_request_title'),
            'body' => __('events.notify_join_request_body', [
                'requester' => $this->requester->name,
                'event' => $this->event->title,
            ]),
            'event_id' => $this->event->id,
            'event_slug' => $this->event->slug,
            'join_request_id' => $this->joinRequest->id,
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'url' => event_route('events.requests', $this->event),
            'api_path' => '/events/'.$this->event->id.'/requests',
        ];
    }
}
