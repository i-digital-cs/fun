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
        return DB::transaction(function () use ($googleUser, $locale) {
            $email = strtolower((string) $googleUser->getEmail());

            $user = User::query()
                ->where('google_id', $googleUser->getId())
                ->orWhere('email', $email)
                ->first();

            if ($user) {
                if ($user->google_id === null) {
                    $user->forceFill(['google_id' => $googleUser->getId()])->save();
                }

                if ($user->email_verified_at === null) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                return $user->refresh();
            }

            return User::query()->create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'password' => null,
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'status' => AccountStatus::PendingDocuments,
                'locale' => $locale ?? app()->getLocale(),
                'profile_image_path' => $googleUser->getAvatar(),
            ]);
        });
    }

    public function login(User $user): void
    {
        Auth::login($user, true);
        session()->regenerate();
    }
}
