<?php

namespace App\Enums\Marketing;

enum MarketingBudgetScopeType: string
{
    case Global = 'global';
    case Platform = 'platform';
    case Account = 'account';
    case Campaign = 'campaign';
}
