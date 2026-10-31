<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventDiscoveryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_event_is_excluded_from_public_discovery_until_published(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Sanctum::actingAs($user);

        $create = $this->postJson('/api/events', [
            'title' => 'Yesterday Draft Meetup',
            'description' => 'Should stay private until publish',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'venue' => 'Park',
            'starts_at' => now()->addDay()->toISOString(),
            'ends_at' => now()->addDay()->addHours(2)->toISOString(),
            'max_participants' => 8,
            'sex' => 'mix',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ])->assertCreated();

        $eventId = (int) $create->json('data.id');
        $this->assertSame('draft', $create->json('data.status'));

        $mine = $this->getJson('/api/my-events')->assertOk();
        $this->assertTrue(collect($mine->json('data'))->pluck('id')->contains($eventId));

        $discover = $this->getJson('/api/events?discovery_mode=nationwide')->assertOk();
        $this->assertFalse(collect($discover->json('data'))->pluck('id')->contains($eventId));

        $this->postJson("/api/events/{$eventId}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $after = $this->getJson('/api/events?discovery_mode=nationwide')->assertOk();
        $this->assertTrue(collect($after->json('data'))->pluck('id')->contains($eventId));

        $search = $this->getJson('/api/events?discovery_mode=nationwide&q='.urlencode('Yesterday Draft Meetup'))
            ->assertOk();
        $this->assertTrue(collect($search->json('data'))->pluck('id')->contains($eventId));
    }

    public function test_city_filter_excludes_events_from_other_cities(): void
    {
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();
        $cairo = City::factory()->create(['name_en' => 'Cairo', 'name_ar' => 'القاهرة']);
        $alex = City::factory()->create(['name_en' => 'Alexandria', 'name_ar' => 'الإسكندرية']);

        $cairoEvent = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $cairo->id,
            'title' => 'Cairo Only',
            'starts_at' => now()->addDays(2),
        ]);
        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $alex->id,
            'title' => 'Alex Only',
            'starts_at' => now()->addDays(2),
        ]);

        $list = $this->getJson('/api/events?city_id='.$cairo->id)->assertOk();
        $ids = collect($list->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($cairoEvent->id));
        $this->assertFalse($ids->contains(
            Event::query()->where('title', 'Alex Only')->value('id')
        ));
    }

    public function test_past_started_events_drop_from_browse_window(): void
    {
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $stale = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Already Started',
            'starts_at' => now()->subDays(2),
            'status' => EventStatus::Published,
        ]);

        $list = $this->getJson('/api/events?discovery_mode=nationwide')->assertOk();
        $this->assertFalse(collect($list->json('data'))->pluck('id')->contains($stale->id));
    }
}
