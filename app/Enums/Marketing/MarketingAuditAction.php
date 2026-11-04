<?php

namespace App\Enums\Marketing;

enum MarketingAuditAction: string
{
    case BudgetPolicyCreated = 'budget_policy_created';
    case BudgetPolicyUpdated = 'budget_policy_updated';
    case BudgetPolicyDisabled = 'budget_policy_disabled';
    case BudgetPolicyEnabled = 'budget_policy_enabled';
}
