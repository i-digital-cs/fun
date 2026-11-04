<?php

namespace App\Services\Marketing\Providers;

use App\Enums\Marketing\MarketingCapability;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Models\Marketing\MarketingAccount;

/**
 * Test double only. Never register as a production driver.
 * Returns empty structural lists — never fabricated performance metrics.
 */
class FakeMarketingProvider extends AbstractMarketingProvider
{
    /**
     * @param  list<MarketingCapability>  $capabilities
     */
    public function __construct(
        private readonly MarketingPlatformCode $code = MarketingPlatformCode::Meta,
        private readonly array $capabilities = [
            MarketingCapability::ReadAccounts,
            MarketingCapability::ReadCampaigns,
        ],
    ) {}

    public function platform(): MarketingPlatformCode
    {
        return $this->code;
    }

    public function capabilities(): array
    {
        return $this->capabilities;
    }

    protected function performListRemoteAccounts(): array
    {
        return [];
    }

    protected function performListCampaigns(MarketingAccount $account): array
    {
        return [];
    }
}
