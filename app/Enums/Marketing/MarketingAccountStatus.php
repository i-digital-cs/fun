<?php

namespace App\Enums\Marketing;

enum MarketingAccountStatus: string
{
    case Active = 'active';
    case NeedsReauth = 'needs_reauth';
    case Disabled = 'disabled';
}
