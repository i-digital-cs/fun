<?php

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case Inappropriate = 'inappropriate';
    case Scam = 'scam';
    case Other = 'other';
}
