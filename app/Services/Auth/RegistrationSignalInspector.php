<?php

namespace App\Services\Auth;

use App\Exceptions\RegistrationBlockedException;
use App\Support\EmailNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Observable-pattern registration abuse signals (no new Flutter-required fields).
 *
 * Honeypot: optional field bots often fill; legitimate Flutter omits it.
 */
class RegistrationSignalInspector
{
    /**
     * @throws RegistrationBlockedException
     */
    public function assertAcceptable(Request $request, string $email, string $name): void
    {
        $this->assertContentLength($request);
        $this->assertHoneypot($request);
        $this->assertNameSignals($name, $request, $email);
        $this->assertEmailSignals($email, $request);
    }

    /**
     * @throws RegistrationBlockedException
     */
    private function assertContentLength(Request $request): void
    {
        $max = max(1024, (int) config('registration.max_content_length', 16384));
        $length = (int) $request->header('Content-Length', 0);
        if ($length > $max) {
            $this->reject($request, 'content_length', (string) $request->input('email', ''));
        }
    }

    /**
     * @throws RegistrationBlockedException
     */
    private function assertHoneypot(Request $request): void
    {
        if (! (bool) config('registration.honeypot_enabled', true)) {
            return;
        }

        $field = (string) config('registration.honeypot_field', 'website');
        if (! $request->exists($field)) {
            return;
        }

        $value = $request->input($field);
        if (is_string($value) && trim($value) === '') {
            return;
        }
        if ($value === null) {
            return;
        }

        $this->reject($request, 'honeypot', (string) $request->input('email', ''));
    }

    /**
     * @throws RegistrationBlockedException
     */
    private function assertNameSignals(string $name, Request $request, string $email): void
    {
        if (! (bool) config('registration.reject_digit_names', true)) {
            return;
        }

        $compact = preg_replace('/\s+/u', '', trim($name)) ?? '';
        if ($compact === '') {
            return;
        }

        // Historical signup-farm pattern: numeric-only display names.
        if (preg_match('/^\d{3,}$/u', $compact) === 1) {
            $this->reject($request, 'digit_name', $email);
        }

        $digits = preg_match_all('/\d/u', $compact) ?: 0;
        $letters = preg_match_all('/\p{L}/u', $compact) ?: 0;
        if ($digits >= 6 && $letters === 0) {
            $this->reject($request, 'digit_name', $email);
        }
    }

    /**
     * @throws RegistrationBlockedException
     */
    private function assertEmailSignals(string $email, Request $request): void
    {
        $normalized = EmailNormalizer::normalize($email);
        $local = explode('@', $normalized, 2)[0] ?? '';

        // Extreme local-part noise rarely used by humans; keep threshold high for testers.
        if (substr_count($local, '.') >= 6 || substr_count($local, '-') >= 6) {
            $this->reject($request, 'email_noise', $normalized);
        }

        if (! (bool) config('registration.reject_disposable_domains', false)) {
            return;
        }

        $domain = strtolower((string) (explode('@', $normalized, 2)[1] ?? ''));
        $blocked = config('registration.disposable_domains', []);
        if (is_array($blocked) && $domain !== '' && in_array($domain, $blocked, true)) {
            $this->reject($request, 'disposable_email', $normalized);
        }
    }

    /**
     * @throws RegistrationBlockedException
     */
    private function reject(Request $request, string $reason, string $email): void
    {
        Log::warning('registration_signal_rejected', [
            'reason' => $reason,
            'channel' => $request->is('api/*') || $request->is('api') ? 'api' : 'web',
            'ip_fp' => substr(hash('sha256', (string) $request->ip().'|'.config('app.key')), 0, 16),
            'email_hash_prefix' => $email !== ''
                ? substr(hash('sha256', EmailNormalizer::normalize($email)), 0, 12)
                : null,
        ]);

        throw new RegistrationBlockedException(reasonCode: 'signal_'.$reason);
    }
}
