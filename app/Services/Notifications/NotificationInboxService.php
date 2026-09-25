<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationInboxService
{
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()->paginate($perPage);
    }

    public function markRead(User $user, string $notificationId): ?DatabaseNotification
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($notificationId)->first();
        if ($notification === null) {
            return null;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $notification->fresh();
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function registerDeviceToken(User $user, string $token, string $platform = 'android'): DeviceToken
    {
        $platform = in_array($platform, ['android', 'ios', 'web'], true) ? $platform : 'android';

        return DeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'last_used_at' => now(),
            ]
        );
    }

    public function forgetDeviceToken(User $user, string $token): void
    {
        DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->delete();
    }
}
