<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventNowPlayingTest extends TestCase
{
    use RefreshDatabase;

    public function test_now_playing_api_returns_running_next_and_previous(): void
    {
        $city = City::factory()->create(['is_active' => true]);
        $category = Category::factory()->create();
        $owner = User::factory()->active()->create();

        $running = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Sunset Run Live',
            'status' => EventStatus::Published,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $next = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Morning Yoga Next',
            'status' => EventStatus::Published,
            'starts_at' => now()->addHours(3),
            'ends_at' => now()->addHours(5),
        ]);

        $previous = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Board Games Previous',
            'status' => EventStatus::Published,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->getJson('/api/events/now-playing?city_id='.$city->id)
            ->assertOk()
            ->assertJsonPath('data.running.id', $running->id)
            ->assertJsonPath('data.running.title', 'Sunset Run Live')
            ->assertJsonPath('data.next.0.id', $next->id)
            ->assertJsonPath('data.previous.0.id', $previous->id);
    }

    public function test_dashboard_shows_now_playing_widget(): void
    {
        $city = City::factory()->create(['is_active' => true]);
        $user = User::factory()->active()->create(['city_id' => $city->id]);
        $owner = User::factory()->active()->create();

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => $city->id,
            'title' => 'Live Meetup Widget',
            'status' => EventStatus::Published,
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addHours(2),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('now-playing', false)
            ->assertSee('Live Meetup Widget', false)
            ->assertSee(__('dashboard.now_playing_live'), false);
    }
}
