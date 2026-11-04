<?php

namespace Tests\Unit\Marketing;

use App\Enums\Marketing\MarketingCapability;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Exceptions\Marketing\UnsupportedMarketingCapabilityException;
use App\Models\Marketing\MarketingAccount;
use App\Services\Marketing\Providers\FakeMarketingProvider;
use App\Services\Marketing\Providers\MetaMarketingProvider;
use App\Services\Marketing\Providers\TikTokMarketingProvider;
use App\Services\Marketing\MarketingPlatformRegistry;
use Tests\TestCase;

class MarketingProviderCapabilityTest extends TestCase
{
    public function test_meta_and_tiktok_have_no_runtime_capabilities_in_phase_1(): void
    {
        $meta = new MetaMarketingProvider;
        $tiktok = new TikTokMarketingProvider;

        $this->assertSame([], $meta->capabilities());
        $this->assertSame([], $tiktok->capabilities());
        $this->assertNotEmpty($meta->plannedCapabilities());
        $this->assertNotEmpty($tiktok->plannedCapabilities());
        $this->assertFalse($meta->supports(MarketingCapability::ReadCampaigns));
    }

    public function test_unsupported_operations_fail_explicitly(): void
    {
        $meta = new MetaMarketingProvider;
        $account = new MarketingAccount;

        $this->expectException(UnsupportedMarketingCapabilityException::class);
        $meta->listCampaigns($account);
    }

    public function test_fake_provider_returns_empty_lists_not_fake_metrics(): void
    {
        $fake = new FakeMarketingProvider(
            MarketingPlatformCode::Meta,
            [MarketingCapability::ReadAccounts, MarketingCapability::ReadCampaigns],
        );

        $this->assertSame([], $fake->listRemoteAccounts());
        $this->assertSame([], $fake->listCampaigns(new MarketingAccount));
    }

    public function test_fake_provider_rejects_write_ops(): void
    {
        $fake = new FakeMarketingProvider(
            MarketingPlatformCode::TikTok,
            [MarketingCapability::ReadAccounts],
        );

        $this->expectException(UnsupportedMarketingCapabilityException::class);
        $fake->updateBudget(new MarketingAccount, ['daily_budget' => 10]);
    }

    public function test_registry_lists_configured_providers(): void
    {
        $registry = new MarketingPlatformRegistry;
        $codes = array_map(static fn ($p) => $p->platform()->value, $registry->all());

        $this->assertContains('meta', $codes);
        $this->assertContains('tiktok', $codes);

        $summaries = $registry->summaries();
        $this->assertFalse($summaries[0]['runtime_ready']);
        $this->assertNotEmpty($summaries[0]['planned_capabilities']);
    }

    public function test_config_defaults_are_safe(): void
    {
        $this->assertTrue(config('marketing.enabled'));
        $this->assertTrue(config('marketing.read_only'));
        $this->assertFalse(config('marketing.execution_enabled'));
        $this->assertFalse(config('marketing.ai_explanations_enabled'));
        $this->assertSame('1', config('marketing.phase'));
    }
}
