<?php

namespace App\Http\Controllers\Web\Admin\Marketing;

use App\Enums\Marketing\MarketingBudgetScopeType;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Marketing\StoreMarketingBudgetPolicyRequest;
use App\Http\Requests\Admin\Marketing\UpdateMarketingBudgetPolicyRequest;
use App\Models\Marketing\MarketingBudgetPolicy;
use App\Services\Marketing\MarketingBudgetPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketingBudgetPolicyController extends Controller
{
    public function index(MarketingBudgetPolicyService $service): View
    {
        return view('admin.marketing.budget-policies.index', [
            'policies' => $service->list(),
            'scopeTypes' => MarketingBudgetScopeType::cases(),
            'defaults' => config('marketing.budget_policy_defaults', []),
        ]);
    }

    public function store(
        StoreMarketingBudgetPolicyRequest $request,
        MarketingBudgetPolicyService $service,
    ): RedirectResponse {
        try {
            $service->create($request->user(), $request->validated(), $request);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['scope_type' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.marketing.budget-policies.index')
            ->with('status', __('marketing.budget_policy_created'));
    }

    public function update(
        UpdateMarketingBudgetPolicyRequest $request,
        MarketingBudgetPolicy $budgetPolicy,
        MarketingBudgetPolicyService $service,
    ): RedirectResponse {
        try {
            $service->update($request->user(), $budgetPolicy, $request->validated(), $request);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['scope_type' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.marketing.budget-policies.index')
            ->with('status', __('marketing.budget_policy_updated'));
    }

    public function disable(
        MarketingBudgetPolicy $budgetPolicy,
        MarketingBudgetPolicyService $service,
    ): RedirectResponse {
        $service->setActive(request()->user(), $budgetPolicy, false, request());

        return redirect()
            ->route('admin.marketing.budget-policies.index')
            ->with('status', __('marketing.budget_policy_disabled'));
    }

    public function enable(
        MarketingBudgetPolicy $budgetPolicy,
        MarketingBudgetPolicyService $service,
    ): RedirectResponse {
        $service->setActive(request()->user(), $budgetPolicy, true, request());

        return redirect()
            ->route('admin.marketing.budget-policies.index')
            ->with('status', __('marketing.budget_policy_enabled'));
    }
}
