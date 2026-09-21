<?php

namespace App\Policies;

use App\Enums\PlatformRole;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }

    public function create(User $user): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }

    public function deactivate(User $user, Category $category): bool
    {
        return $user->platform_role instanceof PlatformRole;
    }
}
