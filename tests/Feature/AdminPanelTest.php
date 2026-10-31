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
            ->assertSee(__('admin.dashboard_command'))
            ->assertSee(__('admin.section_executive'), false)
            ->assertSee(__('admin.section_action'), false)
            ->assertSee(__('admin.kpi_active_accounts'), false)
            ->assertSee(__('admin.chart_activity'), false)
            ->assertSee(__('admin.chart_actions'), false);
    }

    public function test_dashboard_shows_today_activity_counts(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tz = config('app.timezone');
        $today = now($tz)->startOfDay()->addHours(10);
        $yesterday = $today->copy()->subDay();

        $member = User::factory()->active()->create(['created_at' => $today]);
        User::factory()->active()->create(['created_at' => $yesterday]);

        $city = City::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $event = Event::factory()->published()->create([
            'created_by' => $member->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'created_at' => $today,
        ]);
        Event::factory()->published()->create([
            'created_by' => $member->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'created_at' => $yesterday,
        ]);

        $joiner = User::factory()->active()->create();
        \App\Models\EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $joiner->id,
            'status' => \App\Enums\JoinRequestStatus::Accepted,
            'reviewed_at' => $today,
            'created_at' => $today,
            'updated_at' => $today,
        ]);
        \App\Models\EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => User::factory()->active()->create()->id,
            'status' => \App\Enums\JoinRequestStatus::Pending,
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ]);

        EventMessage::query()->create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'body' => 'Hello today',
            'created_at' => $today,
            'updated_at' => $today,
        ]);

        $peer = User::factory()->active()->create();
        [$low, $high] = \App\Models\DirectConversation::orderedPair((int) $member->id, (int) $peer->id);
        $conversation = \App\Models\DirectConversation::query()->create([
            'user_low_id' => $low,
            'user_high_id' => $high,
            'last_message_at' => $today,
        ]);
        \App\Models\DirectMessage::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'body' => 'DM today',
            'created_at' => $today,
            'updated_at' => $today,
        ]);

        \App\Models\Friendship::query()->create([
            'requester_id' => $member->id,
            'addressee_id' => $peer->id,
            'status' => \App\Enums\FriendshipStatus::Accepted,
            'created_at' => $today,
            'updated_at' => $today,
        ]);

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'sess-today-1',
            'user_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('x'),
            'last_activity' => $today->getTimestamp(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.kpi_new_users'), false)
            ->assertSee(__('admin.kpi_events_created'), false)
            ->assertSee(__('admin.kpi_session_active'), false)
            ->assertSee(__('admin.today_metric_friend_requests'), false)
            ->assertSee(__('admin.today_metric_friend_accepts'), false)
            ->assertSee(__('admin.today_metric_event_messages'), false)
            ->assertSee(__('admin.today_metric_direct_messages'), false)
            ->assertViewHas('today', function (array $todayStats): bool {
                return ($todayStats['registrations'] ?? 0) >= 1
                    && ($todayStats['events_created'] ?? 0) >= 1
                    && ($todayStats['join_requests'] ?? 0) >= 1
                    && ($todayStats['joins_accepted'] ?? 0) >= 1
                    && ($todayStats['event_messages'] ?? 0) >= 1
                    && ($todayStats['direct_messages'] ?? 0) >= 1
                    && ($todayStats['friend_requests'] ?? 0) >= 1
                    && ($todayStats['friend_accepts'] ?? 0) >= 1
                    && ($todayStats['active_members'] ?? 0) >= 1;
            });

        $this->actingAs($admin)
            ->get(route('admin.activity.show', ['metric' => 'registrations', 'day' => 'today']))
            ->assertOk()
            ->assertSee($member->email, false)
            ->assertSee(__('admin.activity_rows'), false);

        $this->actingAs($admin)
            ->get(route('admin.activity.show', ['metric' => 'events_created', 'day' => 'today']))
            ->assertOk()
            ->assertSee($event->title, false);

        $this->actingAs($admin)
            ->get(route('admin.activity.show', ['metric' => 'event_messages', 'day' => 'today']))
            ->assertOk()
            ->assertSee('Hello today', false);

        $this->actingAs($admin)
            ->get(route('admin.activity.show', ['metric' => 'direct_messages', 'day' => 'today']))
            ->assertOk()
            ->assertSee('DM today', false);

        $this->actingAs($admin)
            ->get(route('admin.activity.show', ['metric' => 'active_members', 'day' => 'today']))
            ->assertOk()
            ->assertSee($member->email, false);

        $this->actingAs($admin)
            ->get(route('admin.activity.show', ['metric' => 'not_a_metric']))
            ->assertNotFound();
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
