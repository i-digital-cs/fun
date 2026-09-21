<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Published, self::Cancelled], true),
            self::Published => in_array($target, [self::Cancelled, self::Completed], true),
            self::Cancelled, self::Completed => false,
        };
    }
}
