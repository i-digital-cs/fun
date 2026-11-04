<?php

namespace Database\Seeders;

use App\Enums\Marketing\MarketingCapability;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Repositories\Marketing\MarketingPlatformRepository;
use Illuminate\Database\Seeder;

class MarketingPlatformSeeder extends Seeder
{
    public function run(): void
    {
        /** @var MarketingPlatformRepository $repo */
        $repo = app(MarketingPlatformRepository::class);

        $capabilities = array_map(
            static fn (MarketingCapability $c) => $c->value,
            [
                MarketingCapability::ReadAccounts,
                MarketingCapability::ReadCampaigns,
                MarketingCapability::ReadInsights,
                MarketingCapability::UpdateBudget,
                MarketingCapability::PauseCampaign,
                MarketingCapability::ResumeCampaign,
                MarketingCapability::CreateCampaign,
            ],
        );

        foreach (MarketingPlatformCode::cases() as $code) {
            $repo->upsertByCode([
                'code' => $code->value,
                'name' => $code->displayName(),
                'is_enabled' => true,
                'capabilities_json' => $capabilities,
            ]);
        }
    }
}
