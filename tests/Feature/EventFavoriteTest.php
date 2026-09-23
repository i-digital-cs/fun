<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventFavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(): Event
    {
        $owner = User::factory()->active()->create();

        return Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'title' => 'Favorite Meetup',
        ]);
    }

    public function test_active_user_can_toggle_favorite_via_web(): void
    {
        $user = User::factory()->active()->create();
        $event = $this->publishedEvent();

        $this->actingAs($user)
            ->post(route('events.favorite.toggle', $event))
            ->assertRedirect();

        $this->assertDatabaseHas('event_favorites', [
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        $this->actingAs($user)
            ->post(route('events.favorite.toggle', $event))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_favorites', [
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_favorites_index_lists_bookmarked_events(): void
    {
        $user = User::factory()->active()->create();
        $event = $this->publishedEvent();
        $event->update(['title' => 'Saved Yoga Night']);
        $other = $this->publishedEvent();
        $other->update(['title' => 'Unsaved Concert']);

        EventFavorite::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        $this->actingAs($user)
            ->get(route('events.favorites'))
            ->assertOk()
            ->assertSee('Saved Yoga Night')
            ->assertDontSee('Unsaved Concert');
    }

    public function test_api_toggle_and_list_favorites(): void
    {
        $user = User::factory()->active()->create();
        $event = $this->publishedEvent();

        Sanctum::actingAs($user);

        $this->postJson("/api/events/{$event->id}/favorite")
            ->assertOk()
            ->assertJson([
                'is_favorited' => true,
                'event_id' => $event->id,
            ]);

        $this->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonPath('data.0.id', $event->id)
            ->assertJsonPath('data.0.is_favorited', true);

        $this->getJson("/api/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);

        $this->postJson("/api/events/{$event->id}/favorite")
            ->assertOk()
            ->assertJson(['is_favorited' => false]);
    }

    public function test_pending_identity_user_cannot_reach_favorite_endpoint(): void
    {
        $user = User::factory()->create([
            'status' => \App\Enums\AccountStatus::PendingDocuments,
        ]);
        $event = $this->publishedEvent();

        Sanctum::actingAs($user);

        $this->postJson("/api/events/{$event->id}/favorite")
            ->assertForbidden();
    }
}
