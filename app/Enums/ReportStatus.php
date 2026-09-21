<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Reviewed;
    }
}
