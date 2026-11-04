<?php

namespace App\Contracts\Marketing;

use App\Enums\Marketing\MarketingCapability;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Exceptions\Marketing\UnsupportedMarketingCapabilityException;
use App\Models\Marketing\MarketingAccount;

interface MarketingPlatformInterface
{
    public function platform(): MarketingPlatformCode;

    /**
     * Runtime-supported operations in this application build.
     *
     * @return list<MarketingCapability>
     */
    public function capabilities(): array;

    /**
     * Capabilities the provider will expose once HTTP/OAuth is implemented.
     *
     * @return list<MarketingCapability>
     */
    public function plannedCapabilities(): array;

    public function supports(MarketingCapability $capability): bool;

    /**
     * Phase 1: no live HTTP. Phase 2+ will return remote account summaries.
     *
     * @return list<array<string, mixed>>
     *
     * @throws UnsupportedMarketingCapabilityException
     */
    public function listRemoteAccounts(): array;

    /**
     * @return list<array<string, mixed>>
     *
     * @throws UnsupportedMarketingCapabilityException
     */
    public function listCampaigns(MarketingAccount $account): array;

    /**
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     *
     * @throws UnsupportedMarketingCapabilityException
     */
    public function getInsights(MarketingAccount $account, array $query = []): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws UnsupportedMarketingCapabilityException
     */
    public function updateBudget(MarketingAccount $account, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws UnsupportedMarketingCapabilityException
     */
    public function updateCampaignStatus(MarketingAccount $account, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws UnsupportedMarketingCapabilityException
     */
    public function createCampaign(MarketingAccount $account, array $payload): array;
}
