<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_admin(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard'))
            ->assertSee(__('admin.stat_active_users'), false)
            ->assertSee(__('admin.chart_activity'), false);
    }

    public function test_admin_users_lists_active_users_with_event_counts(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $owner = User::factory()->active()->create(['name' => 'Event Owner']);

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Admin Visible Meetup',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Event Owner', false)
            ->assertSee(__('admin.created_events_count', ['count' => 1]), false)
            ->assertSee('Admin Visible Meetup', false);
    }

    public function test_super_admin_can_update_display_name(): void
    {
        Cache::forget(SiteSettingsService::CACHE_KEY);

        $admin = User::factory()->superAdmin()->create();
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/settings', [
            'display_name' => 'Together <b>Cairo</b>',
        ])
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Together Cairo');

        $this->assertSame('Together Cairo', site_display_name());
        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'site_settings.updated',
        ]);
    }

    public function test_moderator_cannot_update_site_settings(): void
    {
        $moderator = User::factory()->moderator()->create();
        Sanctum::actingAs($moderator);

        $this->putJson('/api/admin/settings', [
            'display_name' => 'Nope',
        ])->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_cancel_and_complete_events(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/events/{$event->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $other = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
        ]);

        $this->postJson("/api/admin/events/{$other->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_admin_can_delete_chat_message_and_logs_audit(): void
    {
        $admin = User::factory()->moderator()->create();
        $owner = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
        ]);

        $message = EventMessage::query()->create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'body' => 'Bad spam',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.chat.destroy', [$event, $message]))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_messages', ['id' => $message->id]);
        $this->assertTrue(
            AdminAuditLog::query()->where('action', 'chat.message_deleted')->exists()
        );
    }

    public function test_moderator_cannot_view_audit_log(): void
    {
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)
            ->get(route('admin.audit.index'))
            ->assertForbidden();

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk();
    }

    public function test_home_uses_configured_display_name(): void
    {
        Cache::forget(SiteSettingsService::CACHE_KEY);
        SiteSetting::query()->delete();
        SiteSetting::query()->create(['display_name' => 'Fun Obour']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Fun Obour');
    }

    public function test_admin_identity_and_events_pages_render(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.identity.index'))
            ->assertOk()
            ->assertSee(__('admin.identity_documents'));

        $this->actingAs($admin)
            ->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee(__('admin.events'));
    }
}
