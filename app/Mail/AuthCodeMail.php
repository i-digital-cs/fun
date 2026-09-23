<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $code,
        public readonly string $purpose,
        public readonly string $siteName,
        public readonly ?string $logoUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'password_reset'
            ? __('auth.mail_reset_subject', ['app' => $this->siteName])
            : __('auth.mail_verify_subject', ['app' => $this->siteName]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth-code',
            with: [
                'userName' => $this->user->name,
                'code' => $this->code,
                'purpose' => $this->purpose,
                'siteName' => $this->siteName,
                'logoUrl' => $this->logoUrl,
                'expiresMinutes' => 15,
            ],
        );
    }
}
