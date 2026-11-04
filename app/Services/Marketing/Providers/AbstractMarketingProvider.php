<?php

namespace App\Services\Marketing\Providers;

use App\Contracts\Marketing\MarketingPlatformInterface;
use App\Enums\Marketing\MarketingCapability;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Exceptions\Marketing\UnsupportedMarketingCapabilityException;
use App\Models\Marketing\MarketingAccount;

abstract class AbstractMarketingProvider implements MarketingPlatformInterface
{
    abstract public function platform(): MarketingPlatformCode;

    /**
     * @return list<MarketingCapability>
     */
    abstract public function capabilities(): array;

    /**
     * @return list<MarketingCapability>
     */
    public function plannedCapabilities(): array
    {
        return $this->capabilities();
    }

    public function supports(MarketingCapability $capability): bool
    {
        foreach ($this->capabilities() as $supported) {
            if ($supported === $capability) {
                return true;
            }
        }

        return false;
    }

    public function listRemoteAccounts(): array
    {
        $this->assertSupports(MarketingCapability::ReadAccounts);

        return $this->performListRemoteAccounts();
    }

    public function listCampaigns(MarketingAccount $account): array
    {
        $this->assertSupports(MarketingCapability::ReadCampaigns);

        return $this->performListCampaigns($account);
    }

    public function getInsights(MarketingAccount $account, array $query = []): array
    {
        $this->assertSupports(MarketingCapability::ReadInsights);

        return $this->performGetInsights($account, $query);
    }

    public function updateBudget(MarketingAccount $account, array $payload): array
    {
        $this->assertSupports(MarketingCapability::UpdateBudget);

        return $this->performUpdateBudget($account, $payload);
    }

    public function updateCampaignStatus(MarketingAccount $account, array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? ''));
        $capability = match ($status) {
            'paused', 'pause', 'disable', 'disabled' => MarketingCapability::PauseCampaign,
            'active', 'resume', 'enable', 'enabled' => MarketingCapability::ResumeCampaign,
            default => MarketingCapability::PauseCampaign,
        };

        $this->assertSupports($capability);

        return $this->performUpdateCampaignStatus($account, $payload);
    }

    public function createCampaign(MarketingAccount $account, array $payload): array
    {
        $this->assertSupports(MarketingCapability::CreateCampaign);

        return $this->performCreateCampaign($account, $payload);
    }

    protected function assertSupports(MarketingCapability $capability): void
    {
        if (! $this->supports($capability)) {
            throw new UnsupportedMarketingCapabilityException($this->platform(), $capability);
        }
    }

    /**
     * Phase 1 stubs must not invent live metrics. Override in Phase 2+ HTTP adapters.
     *
     * @return list<array<string, mixed>>
     */
    protected function performListRemoteAccounts(): array
    {
        throw new UnsupportedMarketingCapabilityException($this->platform(), MarketingCapability::ReadAccounts);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function performListCampaigns(MarketingAccount $account): array
    {
        throw new UnsupportedMarketingCapabilityException($this->platform(), MarketingCapability::ReadCampaigns);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    protected function performGetInsights(MarketingAccount $account, array $query = []): array
    {
        throw new UnsupportedMarketingCapabilityException($this->platform(), MarketingCapability::ReadInsights);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function performUpdateBudget(MarketingAccount $account, array $payload): array
    {
        throw new UnsupportedMarketingCapabilityException($this->platform(), MarketingCapability::UpdateBudget);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function performUpdateCampaignStatus(MarketingAccount $account, array $payload): array
    {
        throw new UnsupportedMarketingCapabilityException($this->platform(), MarketingCapability::PauseCampaign);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function performCreateCampaign(MarketingAccount $account, array $payload): array
    {
        throw new UnsupportedMarketingCapabilityException($this->platform(), MarketingCapability::CreateCampaign);
    }
}
