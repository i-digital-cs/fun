<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDraftEventAccessTest extends TestCase
{
    use RefreshDatabase;

    private function draftEvent(User $owner): Event
    {
        return Event::factory()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Draft,
            'title' => 'Admin Draft Preview Fixture',
            'slug' => 'admin-draft-preview-fixture',
        ]);
    }

    public function test_platform_admin_can_preview_draft_without_publishing(): void
    {
        $owner = User::factory()->active()->create();
        $admin = User::factory()->moderator()->create();
        $event = $this->draftEvent($owner);

        $this->actingAs($admin)
            ->get(route('events.show', $event->showRoute()))
            ->assertOk()
            ->assertSee('Admin Draft Preview Fixture', false)
            ->assertSee(__('events.admin_draft_preview'), false)
            ->assertSee('noindex', false);

        $this->assertSame(EventStatus::Draft, $event->fresh()->status);
    }

    public function test_owner_can_open_own_draft(): void
    {
        $owner = User::factory()->active()->create();
        $event = $this->draftEvent($owner);

        $this->actingAs($owner)
            ->get(route('events.show', $event->showRoute()))
            ->assertOk();
    }

    public function test_other_user_and_guest_cannot_open_draft(): void
    {
        $owner = User::factory()->active()->create();
        $other = User::factory()->active()->create();
        $event = $this->draftEvent($owner);

        $this->actingAs($other)
            ->get(route('events.show', $event->showRoute()))
            ->assertForbidden();

        $this->get(route('events.show', $event->showRoute()))
            ->assertForbidden();
    }

    public function test_public_discovery_excludes_draft(): void
    {
        $owner = User::factory()->active()->create();
        $event = $this->draftEvent($owner);

        $list = $this->getJson('/api/events?discovery_mode=nationwide')->assertOk();
        $this->assertFalse(collect($list->json('data'))->pluck('id')->contains($event->id));
    }

    public function test_published_event_still_public(): void
    {
        $owner = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
        ]);

        $this->get(route('events.show', $event->showRoute()))
            ->assertOk()
            ->assertDontSee(__('events.admin_draft_preview'), false);
    }
}
