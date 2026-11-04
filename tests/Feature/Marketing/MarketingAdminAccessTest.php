<?php

namespace Tests\Feature\Marketing;

use App\Models\User;
use Database\Seeders\MarketingPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketingPlatformSeeder::class);
    }

    public function test_guest_is_redirected_from_marketing_overview(): void
    {
        $this->get(route('admin.marketing.overview'))
            ->assertRedirect();
    }

    public function test_regular_user_forbidden(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user)
            ->get(route('admin.marketing.overview'))
            ->assertForbidden();
    }

    public function test_moderator_forbidden(): void
    {
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)
            ->get(route('admin.marketing.overview'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('admin.marketing.integrations.index'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('admin.marketing.budget-policies.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_marketing_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.marketing.overview'))
            ->assertOk()
            ->assertSee(__('marketing.manager_title'), false)
            ->assertSee(__('marketing.empty_accounts_title'), false)
            ->assertDontSee('Cost per click', false)
            ->assertDontSee('$12,345', false);

        $this->actingAs($admin)
            ->get(route('admin.marketing.integrations.index'))
            ->assertOk()
            ->assertSee(__('marketing.status_not_connected'), false)
            ->assertSee(__('marketing.connect_disabled'), false);

        $this->actingAs($admin)
            ->get(route('admin.marketing.budget-policies.index'))
            ->assertOk()
            ->assertSee(__('marketing.budget_disclaimer'), false);
    }

    public function test_arabic_locale_renders_marketing_overview(): void
    {
        $admin = User::factory()->superAdmin()->create(['locale' => 'ar']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('admin.marketing.overview'))
            ->assertOk()
            ->assertSee(__('marketing.manager_title', [], 'ar'), false);
    }
}
