<?php

namespace App\Enums\Marketing;

enum MarketingCapability: string
{
    case ReadAccounts = 'read_accounts';
    case ReadCampaigns = 'read_campaigns';
    case ReadInsights = 'read_insights';
    case CreateCampaign = 'create_campaign';
    case UpdateBudget = 'update_budget';
    case PauseCampaign = 'pause_campaign';
    case ResumeCampaign = 'resume_campaign';

    public function isWrite(): bool
    {
        return match ($this) {
            self::CreateCampaign,
            self::UpdateBudget,
            self::PauseCampaign,
            self::ResumeCampaign => true,
            default => false,
        };
    }
}
