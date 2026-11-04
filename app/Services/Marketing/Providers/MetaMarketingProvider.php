<?php

namespace App\Services\Marketing\Providers;

use App\Enums\Marketing\MarketingCapability;
use App\Enums\Marketing\MarketingPlatformCode;

/**
 * Phase 1 capability declaration only — no Marketing API HTTP calls.
 * Runtime capabilities are empty until Phase 2 OAuth/HTTP adapters ship.
 */
class MetaMarketingProvider extends AbstractMarketingProvider
{
    public function platform(): MarketingPlatformCode
    {
        return MarketingPlatformCode::Meta;
    }

    public function capabilities(): array
    {
        return [];
    }

    public function plannedCapabilities(): array
    {
        return [
            MarketingCapability::ReadAccounts,
            MarketingCapability::ReadCampaigns,
            MarketingCapability::ReadInsights,
            MarketingCapability::UpdateBudget,
            MarketingCapability::PauseCampaign,
            MarketingCapability::ResumeCampaign,
            MarketingCapability::CreateCampaign,
        ];
    }
}
