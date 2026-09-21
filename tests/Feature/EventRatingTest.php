<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventRating;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventRatingTest extends TestCase
{
    use RefreshDatabase;

    private function completedEvent(User $owner, array $attrs = []): Event
    {
        return Event::factory()->create(array_merge([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Completed,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'max_participants' => 5,
        ], $attrs));
    }

    public function test_member_can_rate_creator_after_completed(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->completedEvent($owner);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/events/{$event->id}/ratings", [
            'stars' => 5,
            'comment' => 'Great host <b>ok</b>',
        ])
            ->assertCreated()
            ->assertJsonPath('data.stars', 5)
            ->assertJsonPath('data.comment', 'Great host ok')
            ->assertJsonPath('data.rated_user.id', $owner->id);

        $this->assertDatabaseHas('event_ratings', [
            'event_id' => $event->id,
            'rater_id' => $member->id,
            'rated_user_id' => $owner->id,
            'stars' => 5,
        ]);
    }

    public function test_creator_cannot_rate_self(): void
    {
        $owner = User::factory()->active()->create();
        $event = $this->completedEvent($owner);

        Sanctum::actingAs($owner);

        $this->postJson("/api/events/{$event->id}/ratings", ['stars' => 5])
            ->assertForbidden();
    }

    public function test_non_member_cannot_rate(): void
    {
        $owner = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        $event = $this->completedEvent($owner);

        Sanctum::actingAs($outsider);

        $this->postJson("/api/events/{$event->id}/ratings", ['stars' => 4])
            ->assertForbidden();
    }

    public function test_cannot_rate_before_event_ends(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'status' => EventStatus::Published,
        ]);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/events/{$event->id}/ratings", ['stars' => 4])
            ->assertForbidden();
    }

    public function test_can_rate_when_ends_at_passed_even_if_still_published(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subHour(),
            'status' => EventStatus::Published,
        ]);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/events/{$event->id}/ratings", ['stars' => 3])
            ->assertCreated();
    }

    public function test_rating_can_be_updated(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->completedEvent($owner);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/events/{$event->id}/ratings", ['stars' => 2])->assertCreated();
        $this->postJson("/api/events/{$event->id}/ratings", [
            'stars' => 4,
            'comment' => 'Updated',
        ])->assertCreated()->assertJsonPath('data.stars', 4);

        $this->assertSame(1, EventRating::query()->where('event_id', $event->id)->count());
    }

    public function test_blocked_member_cannot_rate_creator(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->completedEvent($owner);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        UserBlock::query()->create([
            'blocker_id' => $member->id,
            'blocked_id' => $owner->id,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/events/{$event->id}/ratings", ['stars' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('ratings.blocked'));
    }

    public function test_event_show_includes_creator_rating_summary(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->completedEvent($owner);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        EventRating::query()->create([
            'event_id' => $event->id,
            'rater_id' => $member->id,
            'rated_user_id' => $owner->id,
            'stars' => 4,
            'comment' => 'Solid',
        ]);

        Sanctum::actingAs($member);

        $this->getJson("/api/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('ready_for_ratings', true)
            ->assertJsonPath('can_rate', true)
            ->assertJsonPath('creator_rating.average', 4)
            ->assertJsonPath('creator_rating.count', 1)
            ->assertJsonPath('my_rating.stars', 4);
    }

    public function test_web_show_renders_rating_form_for_eligible_member(): void
    {
        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->completedEvent($owner);

        $event->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        $this->actingAs($member)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee(__('ratings.title'))
            ->assertSee(__('ratings.submit'));
    }
}
