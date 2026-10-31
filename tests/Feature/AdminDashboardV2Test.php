<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\ReportableType;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Friendship;
use App\Models\Report;
use App\Models\User;
use App\Services\Admin\Dashboard\DashboardAnalyticsService;
use App\Support\Reporting\ReportingPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_member_cannot_open_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect();

        $member = User::factory()->active()->create();
        $this->actingAs($member)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_dashboard_v2_sections_render_for_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertSee(__('admin.dashboard_command'), false)
            ->assertSee(__('admin.section_action'), false)
            ->assertSee(__('admin.section_activity'), false)
            ->assertSee(__('admin.section_events'), false)
            ->assertSee(__('admin.section_social'), false)
            ->assertSee(__('admin.section_users'), false)
            ->assertSee(__('admin.section_geo'), false)
            ->assertSee(__('admin.section_health'), false)
            ->assertDontSee('private message body should never appear', false);
    }

    public function test_period_filter_and_kpi_accuracy(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tz = config('app.timezone');
        $now = now($tz)->startOfDay()->addHours(12);

        $city = City::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $host = User::factory()->active()->create(['created_at' => $now->copy()->subDays(2)]);

        $event = Event::factory()->published()->create([
            'created_by' => $host->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'created_at' => $now->copy()->subDays(1),
            'title' => 'Period Event Alpha',
        ]);

        Event::factory()->create([
            'created_by' => $host->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Draft,
            'created_at' => $now->copy()->subDays(1),
            'title' => 'Draft Not Approval Queue',
        ]);

        $joiner = User::factory()->active()->create(['created_at' => $now->copy()->subDays(1)]);
        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $joiner->id,
            'role' => \App\Enums\EventMemberRole::Member,
            'created_at' => $now->copy()->subDays(1),
            'updated_at' => $now->copy()->subDays(1),
        ]);

        User::factory()->create([
            'status' => AccountStatus::PendingDocuments,
            'created_at' => $now->copy()->subDays(1),
        ]);

        Report::query()->create([
            'reporter_id' => $joiner->id,
            'reportable_type' => ReportableType::User->value,
            'reportable_id' => $host->id,
            'reason' => ReportReason::Spam,
            'description' => 'test',
            'status' => ReportStatus::Pending,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Friendship::query()->create([
            'requester_id' => $host->id,
            'addressee_id' => $joiner->id,
            'status' => FriendshipStatus::Accepted,
            'created_at' => $now->copy()->subDays(1),
            'updated_at' => $now->copy()->subDays(1),
        ]);

        Cache::flush();

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertSee('Period Event Alpha', false)
            ->assertSee(__('admin.action_pending_reports'), false)
            ->assertViewHas('kpis')
            ->assertViewHas('eventStatus', function (array $statuses): bool {
                return ($statuses['published'] ?? 0) >= 1
                    && ($statuses['draft'] ?? 0) >= 1;
            })
            ->assertViewHas('eventOps', function (array $ops): bool {
                return ($ops['participants_period'] ?? 0) >= 1
                    && ($ops['created_period'] ?? 0) >= 1;
            })
            ->assertViewHas('actionCenter', function (array $items): bool {
                $reports = collect($items)->firstWhere('key', 'pending_reports');

                return $reports && ($reports['count'] ?? 0) >= 1;
            });
    }

    public function test_custom_range_and_zero_previous_percent_is_null(): void
    {
        $period = ReportingPeriod::preset(ReportingPeriod::PRESET_TODAY);
        $service = app(DashboardAnalyticsService::class);
        $admin = User::factory()->superAdmin()->create();

        Cache::flush();
        $payload = $service->build($period, $admin);

        $newUsers = collect($payload['kpis'])->firstWhere('key', 'new_users');
        $this->assertNotNull($newUsers);
        // With empty previous day activity, percent must not invent 100% from zero baseline.
        if (($newUsers['previous'] ?? null) === 0 && ($newUsers['value'] ?? 0) > 0) {
            $this->assertNull($newUsers['percent']);
        }

        $custom = ReportingPeriod::custom(
            now()->subDays(3)->toDateString(),
            now()->toDateString(),
        );
        $this->assertSame(ReportingPeriod::PRESET_CUSTOM, $custom->preset);
        $this->assertLessThanOrEqual(90, $custom->dayCount());
    }

    public function test_dashboard_does_not_expose_private_message_bodies(): void
    {
        $admin = User::factory()->moderator()->create();
        $secret = 'SECRET_DM_BODY_SHOULD_NOT_RENDER_'.$this->fakerUnique();

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        [$low, $high] = \App\Models\DirectConversation::orderedPair((int) $a->id, (int) $b->id);
        $conversation = \App\Models\DirectConversation::query()->create([
            'user_low_id' => $low,
            'user_high_id' => $high,
            'last_message_at' => now(),
        ]);
        \App\Models\DirectMessage::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $a->id,
            'body' => $secret,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::flush();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee($secret, false)
            ->assertSee(__('admin.social_privacy_note'), false);
    }

    public function test_rtl_locale_renders_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create(['locale' => 'ar']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('admin.dashboard_command', [], 'ar'), false);
    }

    public function test_failed_jobs_count_without_exception_leak(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')) {
            $this->markTestSkipped('failed_jobs missing');
        }

        DB::table('failed_jobs')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'SENSITIVE_EXCEPTION_STACKTRACE',
            'failed_at' => now(),
        ]);

        $admin = User::factory()->superAdmin()->create();
        Cache::flush();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.health_failed_jobs'), false)
            ->assertDontSee('SENSITIVE_EXCEPTION_STACKTRACE', false);
    }

    private function fakerUnique(): string
    {
        return bin2hex(random_bytes(8));
    }
}
