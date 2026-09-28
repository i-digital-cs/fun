<?php

namespace App\Services\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppleAuthService
{
    /**
     * @param  array{sub: string, email: ?string, email_verified?: bool, name?: ?string}  $profile
     */
    public function findOrCreateFromIdTokenPayload(array $profile, ?string $locale = null): User
    {
        return DB::transaction(function () use ($profile, $locale) {
            $appleId = (string) $profile['sub'];
            $email = isset($profile['email']) ? strtolower((string) $profile['email']) : null;

            $user = User::query()->where('apple_id', $appleId)->first();

            if (! $user && filled($email)) {
                $user = User::query()->where('email', $email)->first();
            }

            if ($user) {
                $updates = [];

                if ($user->apple_id === null) {
                    $updates['apple_id'] = $appleId;
                }

                if ($user->email_verified_at === null && ($profile['email_verified'] ?? true)) {
                    $updates['email_verified_at'] = now();
                }

                if ($updates !== []) {
                    $user->forceFill($updates)->save();
                }

                return $user->refresh();
            }

            if (! filled($email)) {
                // First-time Apple login without email (or private relay withheld) — synthesize unique email.
                $email = 'apple.'.Str::lower(Str::substr($appleId, 0, 24)).'@privaterelay.appleid.local';
            }

            $name = filled($profile['name'] ?? null)
                ? (string) $profile['name']
                : Str::before($email, '@');

            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => null,
                'apple_id' => $appleId,
                'locale' => $locale ?? app()->getLocale(),
            ]);
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => AccountStatus::PendingDocuments,
            ])->save();

            return $user;
        });
    }
}
