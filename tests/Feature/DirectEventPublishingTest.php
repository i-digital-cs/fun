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

class DirectEventPublishingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(Category $category, City $city): array
    {
        return [
            'title' => 'Direct Publish Meetup',
            'description' => 'Friendly neighborhood meetup',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'venue' => 'Park',
            'starts_at' => now()->addDay()->toISOString(),
            'ends_at' => now()->addDay()->addHours(2)->toISOString(),
            'max_participants' => 10,
            'sex' => 'mix',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ];
    }

    public function test_api_create_without_intent_stays_draft(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', $this->payload($category, $city))
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('published', false);

        $this->assertSame('draft', Event::query()->find($response->json('data.id'))->status->value);
    }

    public function test_api_create_with_publish_intent_publishes_and_is_discoverable(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', array_merge(
            $this->payload($category, $city),
            ['publish_intent' => true]
        ))->assertCreated()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('published', true);

        $eventId = (int) $response->json('data.id');
        $this->assertSame(EventStatus::Published, Event::query()->find($eventId)->status);

        $discover = $this->getJson('/api/events?discovery_mode=nationwide')->assertOk();
        $this->assertTrue(collect($discover->json('data'))->pluck('id')->contains($eventId));
    }

    public function test_web_save_draft_and_publish_buttons(): void
    {
        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $draft = $this->actingAs($user)->post(route('events.store'), array_merge(
            $this->payload($category, $city),
            ['publish_intent' => '0']
        ));
        $draft->assertRedirect();
        $draftId = Event::query()->latest('id')->value('id');
        $this->assertSame(EventStatus::Draft, Event::query()->find($draftId)->status);

        $published = $this->actingAs($user)->post(route('events.store'), array_merge(
            $this->payload($category, $city),
            [
                'title' => 'Published From Web Form',
                'publish_intent' => '1',
            ]
        ));
        $published->assertRedirect();
        $pub = Event::query()->where('title', 'Published From Web Form')->first();
        $this->assertNotNull($pub);
        $this->assertSame(EventStatus::Published, $pub->status);
    }

    public function test_existing_drafts_are_not_bulk_published(): void
    {
        $user = User::factory()->active()->create();
        $existing = Event::factory()->create([
            'created_by' => $user->id,
            'status' => EventStatus::Draft,
            'title' => 'Must Stay Draft',
        ]);

        Sanctum::actingAs($user);
        $this->postJson('/api/events', array_merge(
            $this->payload(Category::factory()->create(), City::factory()->create()),
            ['publish_intent' => true]
        ))->assertCreated();

        $this->assertSame(EventStatus::Draft, $existing->fresh()->status);
    }
}
