<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\MarketingBudgetScopeType;
use Illuminate\Database\Eloquent\Model;

class MarketingBudgetPolicy extends Model
{
    protected $table = 'marketing_budget_policies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'scope_type',
        'scope_id',
        'max_daily_spend',
        'max_monthly_spend',
        'max_budget_increase_pct',
        'max_budget_decrease_pct',
        'allow_status_pause',
        'allow_status_resume',
        'allow_create',
        'currency',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope_type' => MarketingBudgetScopeType::class,
            'max_daily_spend' => 'decimal:2',
            'max_monthly_spend' => 'decimal:2',
            'max_budget_increase_pct' => 'decimal:2',
            'max_budget_decrease_pct' => 'decimal:2',
            'allow_status_pause' => 'boolean',
            'allow_status_resume' => 'boolean',
            'allow_create' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
