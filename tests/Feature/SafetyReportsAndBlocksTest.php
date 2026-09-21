<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMessage;
use App\Models\Report;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SafetyReportsAndBlocksTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(User $owner): Event
    {
        return Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'max_participants' => 5,
        ]);
    }

    public function test_user_can_block_and_unblock_another_user(): void
    {
        $blocker = User::factory()->active()->create();
        $blocked = User::factory()->active()->create();

        Sanctum::actingAs($blocker);

        $this->postJson("/api/users/{$blocked->id}/block")
            ->assertCreated()
            ->assertJsonPath('data.blocked_id', $blocked->id);

        $this->assertDatabaseHas('user_blocks', [
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);

        $this->deleteJson("/api/users/{$blocked->id}/block")
            ->assertOk();

        $this->assertDatabaseMissing('user_blocks', [
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);
    }

    public function test_blocked_user_cannot_join_creator_event(): void
    {
        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        UserBlock::query()->create([
            'blocker_id' => $owner->id,
            'blocked_id' => $applicant->id,
        ]);

        Sanctum::actingAs($applicant);

        $this->postJson("/api/events/{$event->id}/join")
            ->assertUnprocessable()
            ->assertJsonPath('message', __('safety.blocked_cannot_join'));
    }

    public function test_owner_cannot_accept_join_from_blocked_user(): void
    {
        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        $joinRequest = EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        UserBlock::query()->create([
            'blocker_id' => $owner->id,
            'blocked_id' => $applicant->id,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/events/{$event->id}/requests/{$joinRequest->id}/accept")
            ->assertUnprocessable()
            ->assertJsonPath('message', __('safety.blocked_cannot_accept'));
    }

    public function test_user_can_report_event_and_message(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => \App\Enums\EventMemberRole::Member,
        ]);

        $message = EventMessage::query()->create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'body' => 'Hello',
        ]);

        Sanctum::actingAs($member);

        $this->postJson('/api/reports', [
            'reportable_type' => 'event',
            'reportable_id' => $event->id,
            'reason' => 'spam',
            'description' => 'Looks fake',
        ])->assertCreated();

        $this->postJson('/api/reports', [
            'reportable_type' => 'message',
            'reportable_id' => $message->id,
            'reason' => 'harassment',
        ])->assertCreated();

        $this->assertSame(2, Report::query()->count());
    }

    public function test_cannot_report_self_or_duplicate_pending(): void
    {
        $user = User::factory()->active()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/reports', [
            'reportable_type' => 'user',
            'reportable_id' => $user->id,
            'reason' => 'other',
        ])->assertUnprocessable();

        $other = User::factory()->active()->create();

        $this->postJson('/api/reports', [
            'reportable_type' => 'user',
            'reportable_id' => $other->id,
            'reason' => 'spam',
        ])->assertCreated();

        $this->postJson('/api/reports', [
            'reportable_type' => 'user',
            'reportable_id' => $other->id,
            'reason' => 'scam',
        ])->assertUnprocessable();
    }

    public function test_admin_can_review_report(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $reporter = User::factory()->active()->create();
        $target = User::factory()->active()->create();

        $report = Report::query()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => 'user',
            'reportable_id' => $target->id,
            'reason' => 'spam',
            'status' => ReportStatus::Pending,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/reports/{$report->id}", [
            'status' => 'resolved',
            'moderation_action' => 'Warned user',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertSame(ReportStatus::Resolved, $report->fresh()->status);
    }

    public function test_blocked_creator_events_hidden_from_discover(): void
    {
        $viewer = User::factory()->active()->create();
        $blockedOwner = User::factory()->active()->create();
        $otherOwner = User::factory()->active()->create();

        $hidden = $this->publishedEvent($blockedOwner);
        $visible = $this->publishedEvent($otherOwner);

        UserBlock::query()->create([
            'blocker_id' => $viewer->id,
            'blocked_id' => $blockedOwner->id,
        ]);

        Sanctum::actingAs($viewer);

        $ids = collect($this->getJson('/api/events')->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($hidden->id));
        $this->assertTrue($ids->contains($visible->id));
    }

    public function test_web_report_and_blocks_pages_render(): void
    {
        $user = User::factory()->active()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('reports.create'))
            ->assertOk()
            ->assertSee(__('safety.report_title'));

        $this->actingAs($user)
            ->get(route('blocks.index'))
            ->assertOk()
            ->assertSee(__('safety.blocked_users'));

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(__('safety.reports'));
    }
}
