<?php

namespace App\Repositories\Marketing;

use App\Models\Marketing\MarketingBudgetPolicy;
use Illuminate\Support\Collection;

class MarketingBudgetPolicyRepository
{
    /**
     * @return Collection<int, MarketingBudgetPolicy>
     */
    public function allOrdered(): Collection
    {
        return MarketingBudgetPolicy::query()
            ->orderByDesc('active')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): ?MarketingBudgetPolicy
    {
        return MarketingBudgetPolicy::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): MarketingBudgetPolicy
    {
        return MarketingBudgetPolicy::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(MarketingBudgetPolicy $policy, array $attributes): MarketingBudgetPolicy
    {
        $policy->fill($attributes);
        $policy->save();

        return $policy->refresh();
    }
}
