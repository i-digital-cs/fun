<?php

namespace App\Services\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAuthService
{
    public function findOrCreateFromGoogle(SocialiteUser $googleUser, ?string $locale = null): User
    {
        return $this->findOrCreate([
            'sub' => (string) $googleUser->getId(),
            'email' => strtolower((string) $googleUser->getEmail()),
            'name' => $googleUser->getName(),
            'picture' => $googleUser->getAvatar(),
        ], $locale);
    }

    /**
     * @param  array{sub: string, email: string, name?: ?string, picture?: ?string}  $profile
     */
    public function findOrCreateFromIdTokenPayload(array $profile, ?string $locale = null): User
    {
        return $this->findOrCreate($profile, $locale);
    }

    /**
     * @param  array{sub: string, email: string, name?: ?string, picture?: ?string}  $profile
     */
    private function findOrCreate(array $profile, ?string $locale = null): User
    {
        return DB::transaction(function () use ($profile, $locale) {
            $email = strtolower((string) $profile['email']);
            $googleId = (string) $profile['sub'];

            $user = User::query()
                ->where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if ($user) {
                $updates = [];

                if ($user->google_id === null) {
                    $updates['google_id'] = $googleId;
                }

                if ($user->email_verified_at === null) {
                    $updates['email_verified_at'] = now();
                }

                if (! filled($user->profile_image_path) && filled($profile['picture'] ?? null)) {
                    $updates['profile_image_path'] = $profile['picture'];
                }

                if ($updates !== []) {
                    $user->forceFill($updates)->save();
                }

                return $user->refresh();
            }

            $user = new User([
                'name' => filled($profile['name'] ?? null) ? (string) $profile['name'] : $email,
                'email' => $email,
                'password' => null,
                'google_id' => $googleId,
                'locale' => $locale ?? app()->getLocale(),
                'profile_image_path' => $profile['picture'] ?? null,
            ]);
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => AccountStatus::PendingDocuments,
            ])->save();

            return $user;
        });
    }

    public function login(User $user): void
    {
        Auth::login($user, true);
        session()->regenerate();
    }
}
