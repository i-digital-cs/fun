<?php

namespace App\Notifications\Channels;

use App\Services\Notifications\FcmPushService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function __construct(private readonly FcmPushService $fcm)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        /** @var array{title: string, body: string, data?: array<string, string>} $payload */
        $payload = $notification->toFcm($notifiable);

        if (! is_object($notifiable) || ! method_exists($notifiable, 'getKey')) {
            return;
        }

        $this->fcm->sendToUser($notifiable, $payload);
    }
}
