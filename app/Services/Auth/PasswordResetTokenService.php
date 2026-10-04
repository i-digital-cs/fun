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

    public function requestCode(string $email): void
    {
        $user = User::query()->where('email', strtolower($email))->first();

        // Include social-only accounts (null password) so members can set a
        // password via email OTP when Google/Apple sign-in is unavailable.
        if ($user !== null) {
            $this->codes->issueAndSend($user, EmailAuthPurpose::PasswordReset);
        }
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
