<?php

namespace App\Services\Marketing;

use App\Enums\Marketing\MarketingAuditAction;
use App\Enums\Marketing\MarketingBudgetScopeType;
use App\Exceptions\DomainException;
use App\Models\Marketing\MarketingBudgetPolicy;
use App\Models\User;
use App\Repositories\Marketing\MarketingBudgetPolicyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketingBudgetPolicyService
{
    public function __construct(
        private readonly MarketingBudgetPolicyRepository $policies,
        private readonly MarketingAuditService $audit,
    ) {}

    /**
     * @return Collection<int, MarketingBudgetPolicy>
     */
    public function list(): Collection
    {
        return $this->policies->allOrdered();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data, ?Request $request = null): MarketingBudgetPolicy
    {
        $this->assertCanManage($actor);

        $attributes = $this->normalize($data);

        return DB::transaction(function () use ($actor, $attributes, $request) {
            $policy = $this->policies->create($attributes);

            $this->audit->log(
                $actor,
                MarketingAuditAction::BudgetPolicyCreated->value,
                MarketingBudgetPolicy::class,
                $policy->id,
                ['after' => $this->safeSnapshot($policy)],
                $request,
            );

            return $policy;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, MarketingBudgetPolicy $policy, array $data, ?Request $request = null): MarketingBudgetPolicy
    {
        $this->assertCanManage($actor);

        $before = $this->safeSnapshot($policy);
        $attributes = $this->normalize($data, $policy);

        return DB::transaction(function () use ($actor, $policy, $attributes, $before, $request) {
            $updated = $this->policies->update($policy, $attributes);

            $this->audit->log(
                $actor,
                MarketingAuditAction::BudgetPolicyUpdated->value,
                MarketingBudgetPolicy::class,
                $updated->id,
                [
                    'before' => $before,
                    'after' => $this->safeSnapshot($updated),
                ],
                $request,
            );

            return $updated;
        });
    }

    public function setActive(User $actor, MarketingBudgetPolicy $policy, bool $active, ?Request $request = null): MarketingBudgetPolicy
    {
        $this->assertCanManage($actor);

        $before = $this->safeSnapshot($policy);

        return DB::transaction(function () use ($actor, $policy, $active, $before, $request) {
            $updated = $this->policies->update($policy, ['active' => $active]);

            $this->audit->log(
                $actor,
                $active
                    ? MarketingAuditAction::BudgetPolicyEnabled->value
                    : MarketingAuditAction::BudgetPolicyDisabled->value,
                MarketingBudgetPolicy::class,
                $updated->id,
                [
                    'before' => $before,
                    'after' => $this->safeSnapshot($updated),
                ],
                $request,
            );

            return $updated;
        });
    }

    private function assertCanManage(User $actor): void
    {
        if ($actor->platform_role?->canManageMarketing() !== true) {
            throw new DomainException(__('marketing.unauthorized'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, ?MarketingBudgetPolicy $existing = null): array
    {
        $scopeType = MarketingBudgetScopeType::from((string) ($data['scope_type'] ?? $existing?->scope_type?->value ?? 'global'));
        $scopeId = $data['scope_id'] ?? $existing?->scope_id;

        if ($scopeType === MarketingBudgetScopeType::Global) {
            $scopeId = null;
        } elseif ($scopeId === null || $scopeId === '') {
            throw new DomainException(__('marketing.budget_scope_id_required'));
        }

        $currency = strtoupper((string) ($data['currency'] ?? $existing?->currency ?? config('marketing.budget_policy_defaults.currency', 'USD')));

        return [
            'scope_type' => $scopeType->value,
            'scope_id' => $scopeId !== null && $scopeId !== '' ? (int) $scopeId : null,
            'max_daily_spend' => $this->nullableDecimal($data['max_daily_spend'] ?? $existing?->max_daily_spend),
            'max_monthly_spend' => $this->nullableDecimal($data['max_monthly_spend'] ?? $existing?->max_monthly_spend),
            'max_budget_increase_pct' => $this->nullableDecimal($data['max_budget_increase_pct'] ?? $existing?->max_budget_increase_pct),
            'max_budget_decrease_pct' => $this->nullableDecimal($data['max_budget_decrease_pct'] ?? $existing?->max_budget_decrease_pct),
            'allow_status_pause' => (bool) ($data['allow_status_pause'] ?? $existing?->allow_status_pause ?? true),
            'allow_status_resume' => (bool) ($data['allow_status_resume'] ?? $existing?->allow_status_resume ?? true),
            'allow_create' => (bool) ($data['allow_create'] ?? $existing?->allow_create ?? false),
            'currency' => $currency,
            'active' => (bool) ($data['active'] ?? $existing?->active ?? true),
        ];
    }

    private function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    private function safeSnapshot(MarketingBudgetPolicy $policy): array
    {
        return [
            'id' => $policy->id,
            'scope_type' => $policy->scope_type instanceof MarketingBudgetScopeType
                ? $policy->scope_type->value
                : $policy->scope_type,
            'scope_id' => $policy->scope_id,
            'max_daily_spend' => $policy->max_daily_spend,
            'max_monthly_spend' => $policy->max_monthly_spend,
            'max_budget_increase_pct' => $policy->max_budget_increase_pct,
            'max_budget_decrease_pct' => $policy->max_budget_decrease_pct,
            'allow_status_pause' => $policy->allow_status_pause,
            'allow_status_resume' => $policy->allow_status_resume,
            'allow_create' => $policy->allow_create,
            'currency' => $policy->currency,
            'active' => $policy->active,
        ];
    }
}
