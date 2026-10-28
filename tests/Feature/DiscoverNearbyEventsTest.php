<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscoverNearbyEventsTest extends TestCase
{
    use RefreshDatabase;

    private function city(string $name, float $lat, float $lng): City
    {
        return City::factory()->create([
            'name_en' => $name,
            'name_ar' => $name,
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }

    private function publishedIn(City $city, ?Category $category = null, array $attrs = []): Event
    {
        $owner = User::factory()->active()->create();
        $category ??= Category::factory()->create();

        return Event::factory()->published()->create(array_merge([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'title' => $attrs['title'] ?? 'Football '.$city->name_en,
        ], $attrs));
    }

    public function test_guest_sees_nationwide_published_events(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $alex = $this->city('Alexandria', 31.2001, 29.9187);

        $near = $this->publishedIn($obour);
        $far = $this->publishedIn($alex);

        $response = $this->getJson('/api/events');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($near->id));
        $this->assertTrue($ids->contains($far->id));
    }

    public function test_user_nearby_feed_filters_by_city_center_distance(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $alex = $this->city('Alexandria', 31.2001, 29.9187);

        $near = $this->publishedIn($obour);
        $far = $this->publishedIn($alex);

        $user = User::factory()->active()->create([
            'city_id' => $obour->id,
            'distance_km' => 10,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/events');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($near->id));
        $this->assertFalse($ids->contains($far->id));
        $this->assertSame(0.0, (float) collect($response->json('data'))
            ->firstWhere('id', $near->id)['distance_km']);
    }

    public function test_distance_override_includes_farther_cities(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $cairo = $this->city('Cairo', 30.0444, 31.2357);

        $local = $this->publishedIn($obour);
        $cairoEvent = $this->publishedIn($cairo);

        $user = User::factory()->active()->create([
            'city_id' => $obour->id,
            'distance_km' => 10,
        ]);

        Sanctum::actingAs($user);

        $tight = collect($this->getJson('/api/events')->json('data'))->pluck('id');
        $this->assertTrue($tight->contains($local->id));
        $this->assertFalse($tight->contains($cairoEvent->id));

        $wide = collect($this->getJson('/api/events?distance_km=50')->json('data'))->pluck('id');
        $this->assertTrue($wide->contains($local->id));
        $this->assertTrue($wide->contains($cairoEvent->id));
    }

    public function test_category_and_search_filters_apply(): void
    {
        $city = $this->city('Obour', 30.2285, 31.4769);
        $football = Category::factory()->create(['name_en' => 'Football', 'name_ar' => 'كرة']);
        $gaming = Category::factory()->create(['name_en' => 'Gaming', 'name_ar' => 'ألعاب']);

        $match = $this->publishedIn($city, $football, ['title' => 'Friday Football Match']);
        $other = $this->publishedIn($city, $gaming, ['title' => 'PS5 Night']);

        $response = $this->getJson('/api/events?category_id='.$football->id.'&q=Friday');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($match->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_api_includes_join_action_on_cards(): void
    {
        $city = $this->city('Obour', 30.2285, 31.4769);
        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create(['city_id' => $city->id, 'distance_km' => 25]);
        $category = Category::factory()->create();

        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'max_participants' => 5,
        ]);

        EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        Sanctum::actingAs($applicant);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('data.0.join_action', 'request_pending');
    }

    public function test_discover_web_page_renders_event_cards(): void
    {
        $city = $this->city('Obour', 30.2285, 31.4769);
        $event = $this->publishedIn($city, null, [
            'title' => 'Football Night',
            'venue' => 'Al Ahly Club Playground',
            'cost_per_person' => 80,
        ]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee(__('events.discover'))
            ->assertSee('Football Night')
            ->assertSee('Al Ahly Club Playground')
            ->assertSee(__('events.view_event'))
            ->assertSee('80.00 EGP / person');
    }

    public function test_explicit_empty_city_shows_nationwide_for_located_user(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $alex = $this->city('Alexandria', 31.2001, 29.9187);

        $near = $this->publishedIn($obour);
        $far = $this->publishedIn($alex);

        $user = User::factory()->active()->create([
            'city_id' => $obour->id,
            'distance_km' => 10,
        ]);

        Sanctum::actingAs($user);

        $ids = collect($this->getJson('/api/events?city_id=')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($near->id));
        $this->assertTrue($ids->contains($far->id));
    }

    public function test_web_explicit_city_with_no_nearby_does_not_fallback_nationwide(): void
    {
        $hurghada = $this->city('Hurghada', 27.2579, 33.8116);
        $cairo = $this->city('Cairo', 30.0444, 31.2357);

        $this->publishedIn($cairo, null, ['title' => 'Cairo Only Meetup']);

        $user = User::factory()->active()->create([
            'city_id' => $cairo->id,
            'distance_km' => 25,
        ]);

        $this->actingAs($user)
            ->get(route('events.index', [
                'city_id' => $hurghada->id,
                'distance_km' => 5,
            ]))
            ->assertOk()
            ->assertDontSee('Cairo Only Meetup')
            ->assertSee(__('events.empty_nearby_city', [
                'city' => 'Hurghada',
                'km' => 5,
            ]), false)
            ->assertDontSee(__('events.nationwide_notice'), false);
    }

    public function test_device_coordinates_filter_nearby_without_city_id(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $alex = $this->city('Alexandria', 31.2001, 29.9187);

        $near = $this->publishedIn($obour);
        $far = $this->publishedIn($alex);

        $user = User::factory()->active()->create([
            'city_id' => $alex->id,
            'distance_km' => 10,
        ]);

        Sanctum::actingAs($user);

        // Device near Obour — must not fall back to saved Alexandria city.
        $ids = collect($this->getJson('/api/events?latitude=30.2285&longitude=31.4769&distance_km=10')
            ->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($near->id));
        $this->assertFalse($ids->contains($far->id));
    }

    public function test_explicit_city_id_wins_over_device_coordinates(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $alex = $this->city('Alexandria', 31.2001, 29.9187);

        $nearObour = $this->publishedIn($obour);
        $nearAlex = $this->publishedIn($alex);

        Sanctum::actingAs(User::factory()->active()->create());

        $ids = collect($this->getJson(
            '/api/events?city_id='.$alex->id.'&latitude=30.2285&longitude=31.4769&distance_km=10'
        )->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($nearAlex->id));
        $this->assertFalse($ids->contains($nearObour->id));
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->active()->create());

        $this->getJson('/api/events?latitude=999&longitude=31.4')
            ->assertStatus(422);
    }
}
