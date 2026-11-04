<?php

namespace App\Http\Requests\Admin\Marketing;

use App\Enums\Marketing\MarketingBudgetScopeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketingBudgetPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->platform_role?->canManageMarketing() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope_type' => ['required', Rule::enum(MarketingBudgetScopeType::class)],
            'scope_id' => ['nullable', 'integer', 'min:1'],
            'max_daily_spend' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'max_monthly_spend' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'max_budget_increase_pct' => ['nullable', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
            'max_budget_decrease_pct' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'allow_status_pause' => ['sometimes', 'boolean'],
            'allow_status_resume' => ['sometimes', 'boolean'],
            'allow_create' => ['sometimes', 'boolean'],
            'currency' => ['required', 'string', 'size:3'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_status_pause' => $this->boolean('allow_status_pause'),
            'allow_status_resume' => $this->boolean('allow_status_resume'),
            'allow_create' => $this->boolean('allow_create'),
            'active' => $this->boolean('active', true),
            'currency' => strtoupper((string) $this->input('currency', 'USD')),
        ]);
    }
}
