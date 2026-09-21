<?php

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super_admin';
    case Moderator = 'moderator';

    public function canModerateUsers(): bool
    {
        return true;
    }
}
