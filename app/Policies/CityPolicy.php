<?php

namespace App\Policies;

use App\Enums\PlatformRole;
use App\Models\City;
use App\Models\User;

class CityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }

    public function create(User $user): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }

    public function update(User $user, City $city): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }

    public function deactivate(User $user, City $city): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }
}
