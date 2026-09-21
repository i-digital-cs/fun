<?php

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\PlatformRole;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeactivateUserService
{
    public function deactivate(User $actor, User $target, ?string $reason = null): User
    {
        $this->assertCanModerate($actor, $target);

        if ($target->isDeactivated()) {
            throw new DomainException(__('admin.already_deactivated'));
        }

        return DB::transaction(function () use ($actor, $target, $reason) {
            $target->forceFill([
                'status' => AccountStatus::Deactivated,
                'deactivated_at' => now(),
                'deactivated_by' => $actor->id,
                'deactivation_reason' => $reason,
            ])->save();

            $target->tokens()->delete();

            return $target->refresh();
        });
    }

    public function reactivate(User $actor, User $target): User
    {
        $this->assertCanModerate($actor, $target);

        if (! $target->isDeactivated()) {
            throw new DomainException(__('admin.not_deactivated'));
        }

        return DB::transaction(function () use ($target) {
            $hasDocuments = $target->hasRequiredIdentityDocuments();

            $target->forceFill([
                'status' => $hasDocuments ? AccountStatus::Active : AccountStatus::PendingDocuments,
                'deactivated_at' => null,
                'deactivated_by' => null,
                'deactivation_reason' => null,
            ])->save();

            return $target->refresh();
        });
    }

    private function assertCanModerate(User $actor, User $target): void
    {
        if (! $actor->platform_role instanceof PlatformRole) {
            throw new DomainException(__('admin.unauthorized'));
        }

        if ($actor->id === $target->id) {
            throw new DomainException(__('admin.cannot_self'));
        }

        if ($target->platform_role === PlatformRole::SuperAdmin && $actor->platform_role !== PlatformRole::SuperAdmin) {
            throw new DomainException(__('admin.cannot_moderate_super_admin'));
        }
    }
}
