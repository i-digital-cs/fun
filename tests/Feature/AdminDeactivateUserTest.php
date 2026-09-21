<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDeactivateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_deactivate_and_reactivate_user_via_api(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->active()->create();
        $user->createToken('api');

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/users/{$user->id}/deactivate", [
            'reason' => 'Policy violation',
        ])->assertOk()->assertJsonPath('user.status', 'deactivated');

        $user->refresh();
        $this->assertSame(AccountStatus::Deactivated, $user->status);
        $this->assertSame(0, $user->tokens()->count());

        $this->postJson("/api/admin/users/{$user->id}/reactivate")
            ->assertOk()
            ->assertJsonPath('user.status', 'pending_documents');
    }

    public function test_regular_user_cannot_deactivate_others(): void
    {
        $actor = User::factory()->active()->create();
        $target = User::factory()->active()->create();

        Sanctum::actingAs($actor);

        $this->postJson("/api/admin/users/{$target->id}/deactivate")
            ->assertForbidden();
    }

    public function test_moderator_cannot_deactivate_super_admin(): void
    {
        $moderator = User::factory()->moderator()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        Sanctum::actingAs($moderator);

        $this->postJson("/api/admin/users/{$superAdmin->id}/deactivate")
            ->assertForbidden();
    }

    public function test_admin_web_users_index_is_available(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(__('admin.users'), false);
    }
}
