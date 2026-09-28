<?php

namespace App\Services\Account;

use App\Exceptions\DomainException;
use App\Models\DeviceToken;
use App\Models\IdentityDocument;
use App\Models\User;
use App\Notifications\AccountDeletionRequestedNotification;
use App\Support\UserPublicId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountDeletionService
{
    public function graceDays(): int
    {
        return max(1, (int) config('auth.account_deletion_grace_days', 30));
    }

    public function requestDeletion(User $user, ?string $password = null, ?string $providerTokenHint = null): User
    {
        if ($user->deletion_requested_at !== null) {
            throw new DomainException(__('account.deletion_already_requested'));
        }

        if ($user->isPlatformAdmin()) {
            throw new DomainException(__('account.deletion_admin_blocked'));
        }

        $this->assertConfirmation($user, $password, $providerTokenHint);

        return DB::transaction(function () use ($user) {
            $user->forceFill([
                'deletion_requested_at' => now(),
            ])->save();

            $user->tokens()->delete();
            DeviceToken::query()->where('user_id', $user->id)->delete();

            try {
                $user->notify(new AccountDeletionRequestedNotification($this->graceDays()));
            } catch (\Throwable $e) {
                Log::warning('account.deletion.notify_failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $user->refresh();
        });
    }

    public function cancelDeletion(User $user): User
    {
        if ($user->deletion_requested_at === null) {
            throw new DomainException(__('account.deletion_not_pending'));
        }

        $user->forceFill([
            'deletion_requested_at' => null,
        ])->save();

        return $user->refresh();
    }

    public function purgeDueAccounts(): int
    {
        $cutoff = now()->subDays($this->graceDays());
        $count = 0;

        User::query()
            ->whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(50, function ($users) use (&$count): void {
                foreach ($users as $user) {
                    $this->purgeUser($user);
                    $count++;
                }
            });

        return $count;
    }

    public function purgeUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            foreach ($user->identityDocuments()->get() as $document) {
                /** @var IdentityDocument $document */
                try {
                    Storage::disk($document->disk)->delete($document->path);
                } catch (\Throwable) {
                    // continue purge
                }
                $document->delete();
            }

            if (filled($user->profile_image_path) && ! str_starts_with((string) $user->profile_image_path, 'http')) {
                try {
                    Storage::disk('public')->delete($user->profile_image_path);
                } catch (\Throwable) {
                    // ignore
                }
            }

            $user->tokens()->delete();
            DeviceToken::query()->where('user_id', $user->id)->delete();

            $anonEmail = 'deleted+'.$user->id.'.'.Str::lower(Str::random(8)).'@deleted.funnity.local';

            $user->forceFill([
                'name' => 'Deleted User',
                'email' => $anonEmail,
                'password' => null,
                'google_id' => null,
                'apple_id' => null,
                'profile_image_path' => null,
                'sex' => null,
                'city_id' => null,
                'distance_km' => null,
                'remember_token' => null,
                'deletion_requested_at' => null,
                'public_id' => UserPublicId::generate(),
                'email_verified_at' => null,
            ])->save();
        });
    }

    private function assertConfirmation(User $user, ?string $password, ?string $providerTokenHint): void
    {
        if ($user->hasPasswordLogin()) {
            if (! filled($password) || ! Hash::check((string) $password, (string) $user->getAuthPassword())) {
                throw new DomainException(__('account.deletion_password_required'));
            }

            return;
        }

        // Google/Apple-only: client must send confirmation flag after native re-auth.
        if ($providerTokenHint !== 'confirmed') {
            throw new DomainException(__('account.deletion_reauth_required'));
        }
    }
}
