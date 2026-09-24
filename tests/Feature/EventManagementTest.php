<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_create_event_as_draft_with_owner_membership(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'Football — Obour',
            'description' => 'Friendly match',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'venue' => 'Al Ahly Club Playground',
            'starts_at' => now()->addDay()->toISOString(),
            'ends_at' => now()->addDay()->addHours(2)->toISOString(),
            'max_participants' => 10,
            'requirements' => 'Bring water',
            'expected_cost' => 'Pitch shared',
            'cost_per_person' => 80,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.members_count', 1)
            ->assertJsonPath('data.max_participants', 10);

        $eventId = $response->json('data.id');

        $this->assertDatabaseHas('event_members', [
            'event_id' => $eventId,
            'user_id' => $user->id,
            'role' => EventMemberRole::Owner->value,
        ]);
    }

    public function test_create_event_parses_place_from_maps_url_without_api_key(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'Corniche sunset',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'venue' => 'Corniche',
            'maps_url' => 'https://www.google.com/maps?q=30.0444,31.2357',
            'starts_at' => now()->addDay()->toISOString(),
            'max_participants' => 8,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.has_place_pin', true)
            ->assertJsonPath('data.latitude', 30.0444)
            ->assertJsonPath('data.longitude', 31.2357);

        $openUrl = (string) $response->json('data.maps_open_url');
        $this->assertNotEmpty($openUrl);
        $this->assertStringContainsString('30.0444', $openUrl);
        $this->assertStringContainsString('31.2357', $openUrl);
    }

    public function test_create_event_prefers_pin_over_mismatched_maps_link(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'Pin wins meetup',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'venue' => 'Venue',
            // Link points to Alexandria; pin is Cairo — pin must win.
            'maps_url' => 'https://www.google.com/maps?q=31.2001,29.9187',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'starts_at' => now()->addDay()->toISOString(),
            'max_participants' => 8,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.has_place_pin', true)
            ->assertJsonPath('data.latitude', 30.0444)
            ->assertJsonPath('data.longitude', 31.2357);

        $openUrl = (string) $response->json('data.maps_open_url');
        $this->assertStringContainsString('30.0444', $openUrl);
        $this->assertStringContainsString('31.2357', $openUrl);
        $this->assertStringNotContainsString('31.2001', $openUrl);

        $mapsUrl = (string) $response->json('data.maps_url');
        $this->assertStringContainsString('30.0444', $mapsUrl);
    }

    public function test_owner_can_publish_and_public_list_shows_only_published(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $draft = Event::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Draft,
        ]);

        $published = Event::factory()->published()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/events/{$draft->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $list = $this->getJson('/api/events');
        $list->assertOk();
        $ids = collect($list->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($draft->id));
        $this->assertTrue($ids->contains($published->id));
    }

    public function test_cannot_reduce_capacity_below_current_members(): void
    {
        $user = User::factory()->active()->create();
        $event = Event::factory()->create([
            'created_by' => $user->id,
            'max_participants' => 5,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/events/{$event->id}", [
            'max_participants' => 0,
        ])->assertStatus(422);
    }

    public function test_pending_user_cannot_create_event(): void
    {
        $user = User::factory()->create(); // pending_documents
        $category = Category::factory()->create();
        $city = City::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/events', [
            'title' => 'Test',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'starts_at' => now()->addDay()->toISOString(),
            'max_participants' => 5,
        ])->assertForbidden();
    }

    public function test_non_manager_cannot_publish_event(): void
    {
        $owner = User::factory()->active()->create();
        $other = User::factory()->active()->create();
        $event = Event::factory()->create([
            'created_by' => $owner->id,
            'status' => EventStatus::Draft,
        ]);

        Sanctum::actingAs($other);

        $this->postJson("/api/events/{$event->id}/publish")->assertForbidden();
    }

    public function test_web_create_page_renders_for_active_user(): void
    {
        Category::factory()->create();
        City::factory()->create();
        $user = User::factory()->active()->create();

        $this->actingAs($user)
            ->get(route('events.create'))
            ->assertOk()
            ->assertSee(__('events.create_event'), false);
    }
}
