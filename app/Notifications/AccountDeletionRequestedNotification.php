<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $graceDays) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('account.deletion_mail_subject', ['brand' => site_display_name()]))
            ->line(__('account.deletion_mail_line', [
                'days' => $this->graceDays,
                'brand' => site_display_name(),
            ]))
            ->action(__('account.deletion_mail_action'), url('/account-deletion'))
            ->line(__('account.deletion_mail_cancel', ['days' => $this->graceDays]));
    }
}
