<?php

namespace App\Policies;

use App\Enums\PlatformRole;
use App\Models\User;

class UserPolicy
{
    public function deactivate(User $actor, User $target): bool
    {
        return $this->canModerate($actor, $target);
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $this->canModerate($actor, $target);
    }

    public function update(User $actor, User $target): bool
    {
        if (! $actor->platform_role instanceof PlatformRole) {
            return false;
        }

        if ($target->platform_role === PlatformRole::SuperAdmin && $actor->platform_role !== PlatformRole::SuperAdmin) {
            return false;
        }

        return true;
    }

    public function viewIdentityDocuments(User $actor, User $target): bool
    {
        return $actor->platform_role instanceof PlatformRole;
    }

    private function canModerate(User $actor, User $target): bool
    {
        if (! $actor->platform_role instanceof PlatformRole) {
            return false;
        }

        if ($actor->id === $target->id) {
            return false;
        }

        if ($target->platform_role === PlatformRole::SuperAdmin && $actor->platform_role !== PlatformRole::SuperAdmin) {
            return false;
        }

        return true;
    }
}
