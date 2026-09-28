<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_returns_matching_events(): void
    {
        $owner = User::factory()->active()->create();
        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'title' => 'Sunset Yoga Flow',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);
        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'title' => 'Night Market Walk',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(2),
        ]);

        $this->getJson(route('search.suggest', ['q' => 'Yoga', 'type' => 'events']))
            ->assertOk()
            ->assertJsonPath('meta.type', 'events')
            ->assertJsonFragment(['title' => 'Sunset Yoga Flow'])
            ->assertJsonMissing(['title' => 'Night Market Walk']);
    }

    public function test_suggest_requires_two_characters(): void
    {
        $this->getJson(route('search.suggest', ['q' => 'Y']))
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_suggest_finds_recent_past_and_arabic_alias(): void
    {
        $owner = User::factory()->active()->create();
        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'title' => 'فرجة على ماتش الزمالك في الكافيه',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHours(2),
        ]);

        $this->getJson(route('search.suggest', ['q' => 'zamalek', 'type' => 'events']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'فرجة على ماتش الزمالك في الكافيه']);
    }

    public function test_suggest_includes_completed_events(): void
    {
        $owner = User::factory()->active()->create();
        Event::factory()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Completed,
            'title' => 'Zamalek football match',
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDays(3)->addHours(2),
        ]);

        $this->getJson(route('search.suggest', ['q' => 'zamalek', 'type' => 'events']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Zamalek football match']);
    }

    public function test_discover_fragment_returns_partial_html(): void
    {
        $owner = User::factory()->active()->create();
        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'title' => 'Fragment Event',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);

        $this->get(route('events.index', ['q' => 'Fragment', 'fragment' => 1]))
            ->assertOk()
            ->assertSee('Fragment Event')
            ->assertDontSee('<html', false);
    }
}
