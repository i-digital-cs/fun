<?php

namespace App\Enums\Marketing;

enum MarketingPlatformCode: string
{
    case Meta = 'meta';
    case TikTok = 'tiktok';

    public function displayName(): string
    {
        return match ($this) {
            self::Meta => 'Meta Ads',
            self::TikTok => 'TikTok Ads',
        };
    }
}
