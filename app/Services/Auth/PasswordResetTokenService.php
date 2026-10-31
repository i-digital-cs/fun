<?php

namespace App\Services\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PasswordResetTokenService
{
    private const TTL_MINUTES = 15;

    public function __construct(private readonly EmailAuthCodeService $codes)
    {
    }

    /**
     * Always returns a uniform client payload (no email enumeration).
     * Cooldown / delivery failures are swallowed for unknown or cooling accounts.
     *
     * @return array{retry_after_seconds: int, resend_available_at: string, expires_in_minutes: int}
     */
    public function requestCode(string $email): array
    {
        $user = User::query()->where('email', strtolower($email))->first();
        $cooldown = $this->codes->cooldownSeconds();
        $expires = $this->codes->expiresMinutes();

        // Include social-only accounts (null password) so members can set a
        // password via email OTP when Google/Apple sign-in is unavailable.
        if ($user !== null) {
            try {
                $result = $this->codes->issueAndSendIfAllowed($user, EmailAuthPurpose::PasswordReset);

                return [
                    'retry_after_seconds' => $result->retryAfterSeconds,
                    'resend_available_at' => $result->resendAvailableAt->toIso8601String(),
                    'expires_in_minutes' => $result->expiresInMinutes,
                ];
            } catch (DomainException) {
                // Delivery failed — still look successful publicly.
                return $this->codes->cooldownPayload($user, EmailAuthPurpose::PasswordReset);
            }
        }

        return [
            'retry_after_seconds' => $cooldown,
            'resend_available_at' => now()->addSeconds($cooldown)->toIso8601String(),
            'expires_in_minutes' => $expires,
        ];
    }

    /**
     * @throws DomainException
     */
    public function verifyCode(string $email, string $code): string
    {
        $user = User::query()->where('email', strtolower($email))->first();

        if ($user === null) {
            throw new DomainException(__('auth.invalid_code'));
        }

        $this->codes->verify($user, EmailAuthPurpose::PasswordReset, $code);

        $token = Str::random(64);
        Cache::put($this->cacheKey($token), $user->id, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /**
     * @throws DomainException
     */
    public function resetWithToken(string $email, string $token, string $password): User
    {
        $userId = Cache::pull($this->cacheKey($token));

        if (! is_numeric($userId)) {
            throw new DomainException(__('auth.reset_session_expired'));
        }

        $user = User::query()->find((int) $userId);

        if ($user === null || strtolower($user->email) !== strtolower($email)) {
            throw new DomainException(__('auth.reset_session_expired'));
        }

        $user->forceFill([
            'password' => $password,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user->fresh();
    }

    private function cacheKey(string $token): string
    {
        return 'password_reset_token:'.$token;
    }
}
