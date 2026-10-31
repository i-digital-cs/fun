<?php

namespace App\Services\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Exceptions\OtpCooldownException;
use App\Mail\AuthCodeMail;
use App\Models\EmailAuthCode;
use App\Models\User;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailAuthCodeService
{
    public function __construct(private readonly SiteSettingsService $siteSettings)
    {
    }

    public function cooldownSeconds(): int
    {
        return max(1, (int) config('otp.resend_cooldown_seconds', 60));
    }

    public function expiresMinutes(): int
    {
        return max(1, (int) config('otp.expires_minutes', 15));
    }

    public function maxVerifyAttempts(): int
    {
        return max(1, (int) config('otp.max_verify_attempts', 5));
    }

    /**
     * Seconds remaining before another code may be issued (0 = ready).
     */
    public function cooldownRemaining(User $user, EmailAuthPurpose $purpose): int
    {
        $availableAt = Cache::get($this->cooldownCacheKey($user, $purpose));

        if (! is_numeric($availableAt)) {
            return 0;
        }

        return max(0, (int) $availableAt - time());
    }

    public function resendAvailableAt(User $user, EmailAuthPurpose $purpose): \Carbon\CarbonInterface
    {
        $remaining = $this->cooldownRemaining($user, $purpose);

        return now()->addSeconds($remaining);
    }

    /**
     * Issue a fresh 4-digit code, invalidate prior ones, and email it.
     *
     * @throws OtpCooldownException when a prior successful send is still cooling down
     * @throws DomainException when mail delivery fails (no cooldown applied)
     */
    public function issueAndSend(User $user, EmailAuthPurpose $purpose): EmailAuthIssueResult
    {
        $lock = Cache::lock($this->issueLockKey($user, $purpose), 10);

        return $lock->block(5, function () use ($user, $purpose): EmailAuthIssueResult {
            $remaining = $this->cooldownRemaining($user, $purpose);
            if ($remaining > 0) {
                $availableAt = $this->resendAvailableAt($user, $purpose);
                throw new OtpCooldownException(
                    __('auth.code_cooldown', ['seconds' => $remaining]),
                    $remaining,
                    $availableAt->toIso8601String(),
                );
            }

            return $this->createAndDeliver($user, $purpose);
        });
    }

    /**
     * Auto-send for register/login: skip silently when cooldown is active.
     */
    public function issueAndSendIfAllowed(User $user, EmailAuthPurpose $purpose): EmailAuthIssueResult
    {
        try {
            return $this->issueAndSend($user, $purpose);
        } catch (OtpCooldownException $exception) {
            return new EmailAuthIssueResult(
                issued: false,
                retryAfterSeconds: $exception->retryAfterSeconds,
                resendAvailableAt: now()->addSeconds($exception->retryAfterSeconds),
                expiresInMinutes: $this->expiresMinutes(),
            );
        }
    }

    /**
     * Cooldown / expiry metadata for the OTP screen (no side effects).
     *
     * @return array{retry_after_seconds: int, resend_available_at: string, expires_in_minutes: int}
     */
    public function cooldownPayload(User $user, EmailAuthPurpose $purpose): array
    {
        $remaining = $this->cooldownRemaining($user, $purpose);

        return [
            'retry_after_seconds' => $remaining,
            'resend_available_at' => $this->resendAvailableAt($user, $purpose)->toIso8601String(),
            'expires_in_minutes' => $this->expiresMinutes(),
        ];
    }

    /**
     * @throws DomainException
     */
    public function verify(User $user, EmailAuthPurpose $purpose, string $code): void
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== 4) {
            throw new DomainException(__('auth.invalid_code'));
        }

        $record = EmailAuthCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($record === null || $record->isExpired()) {
            throw new DomainException(__('auth.code_expired'));
        }

        if (! Hash::check($code, $record->code_hash)) {
            $this->registerFailedAttempt($record);
            throw new DomainException(__('auth.invalid_code'));
        }

        $record->forceFill(['consumed_at' => now()])->save();
        Cache::forget($this->attemptsCacheKey($record));
        Cache::forget($this->cooldownCacheKey($user, $purpose));
    }

    private function createAndDeliver(User $user, EmailAuthPurpose $purpose): EmailAuthIssueResult
    {
        EmailAuthCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $plain = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiresMinutes = $this->expiresMinutes();

        $record = EmailAuthCode::query()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($plain),
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        try {
            Mail::to($user->email)->send(new AuthCodeMail(
                user: $user,
                code: $plain,
                purpose: $purpose->value,
                siteName: $this->siteSettings->displayName(),
                logoUrl: $this->siteSettings->logoUrl(),
            ));
        } catch (Throwable $exception) {
            Log::warning('OTP email delivery failed', [
                'user_id' => $user->id,
                'purpose' => $purpose->value,
                'exception' => $exception::class,
            ]);

            // Leave code in place for a later successful delivery, but do not
            // start cooldown so the user can resend immediately.
            throw new DomainException(__('auth.code_delivery_failed'));
        }

        $cooldown = $this->cooldownSeconds();
        $availableAt = now()->addSeconds($cooldown);
        Cache::put(
            $this->cooldownCacheKey($user, $purpose),
            $availableAt->getTimestamp(),
            $availableAt,
        );
        Cache::forget($this->attemptsCacheKey($record));

        return new EmailAuthIssueResult(
            issued: true,
            retryAfterSeconds: $cooldown,
            resendAvailableAt: $availableAt,
            expiresInMinutes: $expiresMinutes,
        );
    }

    private function registerFailedAttempt(EmailAuthCode $record): void
    {
        $key = $this->attemptsCacheKey($record);
        $attempts = (int) Cache::get($key, 0) + 1;
        $ttl = max(1, $record->expires_at->getTimestamp() - time());
        Cache::put($key, $attempts, now()->addSeconds($ttl));

        if ($attempts >= $this->maxVerifyAttempts()) {
            $record->forceFill(['consumed_at' => now()])->save();
            Cache::forget($key);
            throw new DomainException(__('auth.too_many_verify_attempts'));
        }
    }

    private function cooldownCacheKey(User $user, EmailAuthPurpose $purpose): string
    {
        return 'otp:cooldown:'.$user->id.':'.$purpose->value;
    }

    private function issueLockKey(User $user, EmailAuthPurpose $purpose): string
    {
        return 'otp:issue-lock:'.$user->id.':'.$purpose->value;
    }

    private function attemptsCacheKey(EmailAuthCode $record): string
    {
        return 'otp:attempts:'.$record->id;
    }
}
