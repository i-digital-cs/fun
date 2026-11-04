<?php

namespace App\Console\Commands;

use App\Mail\BrevoDeliveryTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Opt-in Brevo SMTP deliverability probe. Does not change the default mailer or OTP flows.
 */
class MailTestBrevoCommand extends Command
{
    protected $signature = 'funnity:mail-test-brevo
                            {--to= : Required recipient email address}';

    protected $description = 'Send one isolated Brevo SMTP delivery test (does not change the default mailer)';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));

        if ($to === '') {
            $this->error('Missing required --to= recipient.');

            return self::FAILURE;
        }

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid recipient email address.');

            return self::FAILURE;
        }

        $username = (string) config('mail.mailers.brevo.username');
        $password = (string) config('mail.mailers.brevo.password');
        $host = (string) config('mail.mailers.brevo.host');

        if ($username === '' || $password === '' || $host === '') {
            $this->error('Brevo SMTP is not fully configured.');
            $this->line('Populate BREVO_MAIL_PASSWORD (and related BREVO_MAIL_* keys) in the local .env.');
            $this->line('Default mailer was not changed. No message was sent.');

            return self::FAILURE;
        }

        $this->info('Brevo mailer probe');
        $this->line('Recipient: '.$to);
        $this->line('Mailer: brevo');
        $this->line('Host: '.$host);
        $this->line('From: '.(string) (config('mail.mailers.brevo.from.address') ?: 'info@funnity.com'));
        $this->warn('SMTP acceptance ≠ Inbox placement. Confirm delivery in the recipient Gmail account.');

        try {
            Mail::mailer('brevo')
                ->to($to)
                ->send(new BrevoDeliveryTestMail);

            $this->info('SMTP connection and submission: accepted by transport (message handed to Brevo).');
            $this->line('Sent: 1 message');
            $this->line('Inbox/Spam placement: not verified by this command.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('SMTP connection or submission failed.');
            $this->line('Recipient: '.$to);
            $this->line('Error: '.$this->sanitizeError($e));

            return self::FAILURE;
        }
    }

    private function sanitizeError(Throwable $e): string
    {
        $message = $e->getMessage();
        $password = (string) config('mail.mailers.brevo.password');
        $username = (string) config('mail.mailers.brevo.username');

        if ($password !== '') {
            $message = str_replace($password, '[redacted]', $message);
        }

        if ($username !== '') {
            $message = str_replace($username, '[redacted-user]', $message);
        }

        // Strip common credential query/userinfo patterns without echoing secrets.
        $message = preg_replace('/:[^@\s]{6,}@/', ':[redacted]@', $message) ?? $message;
        $message = preg_replace('/(password|passwd|pwd|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $message) ?? $message;

        return trim($message) !== '' ? trim($message) : class_basename($e).' (details redacted)';
    }
}
