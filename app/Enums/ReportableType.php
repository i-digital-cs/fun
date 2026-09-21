<?php

namespace App\Enums;

enum ReportableType: string
{
    case User = 'user';
    case Event = 'event';
    case Message = 'message';

    public function modelClass(): string
    {
        return match ($this) {
            self::User => \App\Models\User::class,
            self::Event => \App\Models\Event::class,
            self::Message => \App\Models\EventMessage::class,
        };
    }
}
