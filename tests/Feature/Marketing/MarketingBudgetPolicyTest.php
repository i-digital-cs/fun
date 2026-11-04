<?php

namespace Tests\Feature\Marketing;

use App\Enums\Marketing\MarketingAuditAction;
use App\Models\Marketing\MarketingAuditLog;
use App\Models\Marketing\MarketingBudgetPolicy;
use App\Models\User;
use Database\Seeders\MarketingPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingBudgetPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketingPlatformSeeder::class);
    }

    public function test_super_admin_can_create_budget_policy_with_audit(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->post(route('admin.marketing.budget-policies.store'), [
            'scope_type' => 'global',
            'max_daily_spend' => '100.50',
            'max_monthly_spend' => '1000.00',
            'max_budget_increase_pct' => '20',
            'max_budget_decrease_pct' => '50',
            'allow_status_pause' => '1',
            'allow_status_resume' => '1',
            'currency' => 'usd',
            'active' => '1',
        ]);

        $response->assertRedirect(route('admin.marketing.budget-policies.index'));

        $this->assertDatabaseHas('marketing_budget_policies', [
            'scope_type' => 'global',
            'currency' => 'USD',
            'active' => 1,
        ]);

        $this->assertDatabaseHas('marketing_audit_logs', [
            'actor_id' => $admin->id,
            'action' => MarketingAuditAction::BudgetPolicyCreated->value,
        ]);

        $log = MarketingAuditLog::query()->first();
        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('encrypted_payload', $log->meta_json ?? []);
    }

    public function test_budget_policy_form_includes_csrf_token_field(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.marketing.budget-policies.index'))
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('method="POST"', false);
    }

    public function test_moderator_cannot_create_budget_policy(): void
    {
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)
            ->post(route('admin.marketing.budget-policies.store'), [
                'scope_type' => 'global',
                'currency' => 'USD',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('marketing_budget_policies', 0);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->from(route('admin.marketing.budget-policies.index'))
            ->post(route('admin.marketing.budget-policies.store'), [
                'scope_type' => 'not-a-scope',
                'currency' => 'US',
                'max_daily_spend' => '-5',
            ])
            ->assertSessionHasErrors(['scope_type', 'currency', 'max_daily_spend']);
    }

    public function test_non_global_scope_requires_scope_id(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->from(route('admin.marketing.budget-policies.index'))
            ->post(route('admin.marketing.budget-policies.store'), [
                'scope_type' => 'platform',
                'currency' => 'USD',
            ])
            ->assertSessionHasErrors('scope_type');
    }

    public function test_disable_policy_is_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $policy = MarketingBudgetPolicy::query()->create([
            'scope_type' => 'global',
            'currency' => 'USD',
            'active' => true,
            'allow_status_pause' => true,
            'allow_status_resume' => true,
            'allow_create' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.marketing.budget-policies.disable', $policy))
            ->assertRedirect(route('admin.marketing.budget-policies.index'));

        $this->assertFalse($policy->fresh()->active);
        $this->assertDatabaseHas('marketing_audit_logs', [
            'action' => MarketingAuditAction::BudgetPolicyDisabled->value,
            'subject_id' => $policy->id,
        ]);
    }
}
