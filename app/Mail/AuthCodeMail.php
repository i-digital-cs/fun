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

    /**
     * Presentation context only — does not change OTP security purpose.
     * Allowed: verify | register | login | email_change | password_reset
     */
    public function __construct(
        public readonly User $user,
        public readonly string $code,
        public readonly string $purpose,
        public readonly string $siteName,
        public readonly ?string $logoUrl,
        public readonly int $expiresMinutes = 15,
        public readonly string $mailContext = 'verify',
    ) {
        $locale = $user->locale;
        if (is_string($locale) && in_array($locale, ['en', 'ar'], true)) {
            $this->locale($locale);
        }
    }

    public function envelope(): Envelope
    {
        $locale = $this->recipientLocale();
        $context = $this->resolvedContext();

        $key = match ($context) {
            'password_reset' => 'auth.mail_reset_subject',
            'login' => 'auth.mail_login_subject',
            'email_change' => 'auth.mail_email_change_subject',
            default => 'auth.mail_verify_subject',
        };

        return new Envelope(
            subject: trans($key, ['app' => $this->siteName], $locale),
        );
    }

    public function content(): Content
    {
        $locale = $this->recipientLocale();

        return new Content(
            html: 'emails.auth-code',
            text: 'emails.auth-code-text',
            with: [
                'greeting' => $this->greetingLine($locale),
                'userName' => $this->displayName(),
                'code' => $this->code,
                'purpose' => $this->purpose,
                'mailContext' => $this->resolvedContext(),
                'siteName' => $this->siteName,
                'logoUrl' => $this->logoUrl,
                'expiresMinutes' => max(1, $this->expiresMinutes),
                'locale' => $locale,
            ],
        );
    }

    /**
     * Display-only name for greetings. Does not mutate the stored user name.
     * Returns null when the stored value is missing or whitespace-only.
     * Short legitimate names (including 2-character names) are preserved.
     */
    public function displayName(): ?string
    {
        return self::usableDisplayName($this->user->name);
    }

    public static function usableDisplayName(mixed $name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', $name);
        $trimmed = is_string($normalized) ? trim($normalized) : trim($name);

        return $trimmed === '' ? null : $trimmed;
    }

    public function greetingLine(?string $locale = null): string
    {
        $locale ??= $this->recipientLocale();
        $name = $this->displayName();

        if ($name === null) {
            return trans('auth.mail_greeting_neutral', [], $locale);
        }

        return trans('auth.mail_greeting', ['name' => $name], $locale);
    }

    private function resolvedContext(): string
    {
        if ($this->purpose === 'password_reset') {
            return 'password_reset';
        }

        $context = strtolower(trim($this->mailContext));

        return in_array($context, ['login', 'email_change', 'register', 'verify'], true)
            ? ($context === 'register' ? 'verify' : $context)
            : 'verify';
    }

    private function recipientLocale(): string
    {
        $locale = $this->user->locale;

        return is_string($locale) && in_array($locale, ['en', 'ar'], true)
            ? $locale
            : (string) config('app.locale', 'en');
    }
}
