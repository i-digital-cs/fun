<?php

namespace App\Services\Auth;

use App\Enums\OutboundOtpLane;
use App\Exceptions\RegistrationBlockedException;
use App\Support\EmailNormalizer;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Shared Web+API registration abuse controls.
 *
 * Order of operations for callers:
 *  1. assertMayRegister() — budgets + optional OTP capacity (no side effects beyond locks)
 *  2. create user
 *  3. recordAccepted() — commit counters after the user row exists
 *
 * Concurrent same-email requests are serialized via an atomic cache lock.
 */
class RegistrationAbuseGuard
{
    public function __construct(
        private readonly OutboundOtpBudget $outboundBudget,
    ) {
    }

    /**
     * @param  'web'|'api'  $channel
     * @return \Illuminate\Contracts\Cache\Lock  Hold until register() finishes (success or fail).
     *
     * @throws RegistrationBlockedException
     */
    public function assertMayRegister(string $ip, string $email, string $channel = 'web'): \Illuminate\Contracts\Cache\Lock
    {
        if (! (bool) config('registration.enabled', true)) {
            $this->alert('registration_disabled', $ip, $email, ['channel' => $channel]);
            throw new RegistrationBlockedException(reasonCode: 'disabled');
        }

        $emailNorm = EmailNormalizer::normalize($email);
        $emailHash = hash('sha256', $emailNorm);
        $ipKey = $this->ipFingerprint($ip);

        $lock = Cache::lock($this->emailLockKey($emailHash), 15);

        try {
            $acquired = $lock->block(5);
        } catch (LockTimeoutException) {
            $acquired = false;
        }

        if (! $acquired) {
            $this->alert('registration_email_lock_timeout', $ip, $email, [
                'email_hash_prefix' => substr($emailHash, 0, 12),
                'channel' => $channel,
            ]);
            throw new RegistrationBlockedException(reasonCode: 'concurrent');
        }

        try {
            if (! $this->withinLimit($this->hourKey('global'), (int) config('registration.global_per_hour', 40))
                || ! $this->withinLimit($this->dayKey('global'), (int) config('registration.global_per_day', 200))) {
                $this->alert('registration_global_budget', $ip, $email, ['channel' => $channel]);

                throw new RegistrationBlockedException(reasonCode: 'global_budget');
            }

            if (! $this->withinLimit($this->hourKey('ip:'.$ipKey), (int) config('registration.ip_per_hour', 12))) {
                $this->alert('registration_ip_hour_budget', $ip, $email, [
                    'ip_fp' => $ipKey,
                    'channel' => $channel,
                ]);

                throw new RegistrationBlockedException(reasonCode: 'ip_hour_budget');
            }

            $ipDayLimit = $channel === 'api'
                ? (int) config('registration.api_ip_per_day', 25)
                : (int) config('registration.ip_per_day', 40);

            if (! $this->withinLimit($this->dayKey('ip:'.$ipKey), $ipDayLimit)) {
                $this->alert('registration_ip_budget', $ip, $email, [
                    'ip_fp' => $ipKey,
                    'channel' => $channel,
                    'limit' => $ipDayLimit,
                ]);

                throw new RegistrationBlockedException(reasonCode: 'ip_budget');
            }

            if (! $this->withinLimit($this->hourKey('em:'.$emailHash), (int) config('registration.email_per_hour', 3))) {
                $this->alert('registration_email_budget', $ip, $email, [
                    'email_hash_prefix' => substr($emailHash, 0, 12),
                    'channel' => $channel,
                ]);

                throw new RegistrationBlockedException(reasonCode: 'email_budget');
            }

            if ((bool) config('registration.require_otp_budget', true)
                && ! $this->outboundBudget->allow(OutboundOtpLane::AbuseProne)) {
                $this->alert('registration_otp_budget', $ip, $email, ['channel' => $channel]);

                throw new RegistrationBlockedException(reasonCode: 'otp_budget');
            }
        } catch (RegistrationBlockedException $exception) {
            $lock->release();
            throw $exception;
        }

        return $lock;
    }

    public function recordAccepted(string $ip, string $email): void
    {
        $emailHash = hash('sha256', EmailNormalizer::normalize($email));
        $ipKey = $this->ipFingerprint($ip);

        $this->bump($this->hourKey('global'), now()->endOfHour());
        $this->bump($this->dayKey('global'), now()->endOfDay());
        $this->bump($this->hourKey('ip:'.$ipKey), now()->endOfHour());
        $this->bump($this->dayKey('ip:'.$ipKey), now()->endOfDay());
        $this->bump($this->hourKey('em:'.$emailHash), now()->endOfHour());
    }

    private function withinLimit(string $key, int $limit): bool
    {
        $limit = max(1, $limit);

        return (int) Cache::get($key, 0) < $limit;
    }

    private function bump(string $key, \DateTimeInterface $expiresAt): void
    {
        Cache::add($key, 0, $expiresAt);
        Cache::increment($key);
    }

    private function hourKey(string $scope): string
    {
        return 'reg:budget:'.$scope.':hour:'.now()->format('YmdH');
    }

    private function dayKey(string $scope): string
    {
        return 'reg:budget:'.$scope.':day:'.now()->format('Ymd');
    }

    private function emailLockKey(string $emailHash): string
    {
        return 'reg:lock:em:'.$emailHash;
    }

    /**
     * Privacy-conscious IP fingerprint (full IP stays in structured logs only when needed).
     */
    private function ipFingerprint(string $ip): string
    {
        return substr(hash('sha256', $ip.'|'.(string) config('app.key')), 0, 16);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function alert(string $event, string $ip, string $email, array $extra = []): void
    {
        Log::warning($event, array_merge([
            'ip_fp' => $this->ipFingerprint($ip),
            'email_hash_prefix' => substr(hash('sha256', EmailNormalizer::normalize($email)), 0, 12),
        ], $extra));
    }
}
