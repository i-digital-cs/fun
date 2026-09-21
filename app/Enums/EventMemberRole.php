<?php

namespace App\Enums;

enum EventMemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function canManageEvent(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
