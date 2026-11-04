<?php

namespace App\Services\Marketing;

use App\Repositories\Marketing\MarketingAccountRepository;
use App\Repositories\Marketing\MarketingBudgetPolicyRepository;
use App\Repositories\Marketing\MarketingPlatformRepository;

class MarketingOverviewService
{
    public function __construct(
        private readonly MarketingPlatformRepository $platforms,
        private readonly MarketingAccountRepository $accounts,
        private readonly MarketingBudgetPolicyRepository $policies,
        private readonly MarketingPlatformRegistry $registry,
    ) {}

    /**
     * Honest readiness snapshot — never invents spend/clicks/ROAS.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $platformRows = $this->platforms->allOrdered();
        $accountCount = $this->accounts->countConnected();
        $activePolicies = $this->policies->allOrdered()->where('active', true)->count();

        $integrations = [];
        foreach ($this->registry->summaries() as $summary) {
            $db = $platformRows->firstWhere('code', $summary['code']);
            $connectedAccounts = $db
                ? $db->accounts()->count()
                : 0;

            $integrations[] = [
                'code' => $summary['code'],
                'name' => $summary['name'],
                'enabled' => $summary['enabled'] && (bool) ($db?->is_enabled ?? true),
                'connected' => $connectedAccounts > 0,
                'account_count' => $connectedAccounts,
                'status' => $connectedAccounts > 0 ? 'connected' : 'not_connected',
                'capabilities' => $summary['planned_capabilities'],
                'runtime_ready' => $summary['runtime_ready'],
            ];
        }

        return [
            'phase' => (string) config('marketing.phase', '1'),
            'module_enabled' => (bool) config('marketing.enabled', true),
            'read_only' => (bool) config('marketing.read_only', true),
            'execution_enabled' => (bool) config('marketing.execution_enabled', false),
            'ai_explanations_enabled' => (bool) config('marketing.ai_explanations_enabled', false),
            'platforms_seeded' => $platformRows->count(),
            'connected_accounts' => $accountCount,
            'active_budget_policies' => $activePolicies,
            'integrations' => $integrations,
            'checklist' => [
                [
                    'key' => 'platforms_seeded',
                    'done' => $platformRows->count() >= 2,
                ],
                [
                    'key' => 'budget_policy',
                    'done' => $activePolicies > 0,
                ],
                [
                    'key' => 'meta_connected',
                    'done' => collect($integrations)->firstWhere('code', 'meta')['connected'] ?? false,
                ],
                [
                    'key' => 'tiktok_connected',
                    'done' => collect($integrations)->firstWhere('code', 'tiktok')['connected'] ?? false,
                ],
                [
                    'key' => 'phase2_oauth',
                    'done' => false,
                ],
            ],
        ];
    }
}
