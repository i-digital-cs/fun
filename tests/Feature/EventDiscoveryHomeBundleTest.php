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

class EventDiscoveryHomeBundleTest extends TestCase
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

    private function publishedIn(City $city): Event
    {
        return Event::factory()->published()->create([
            'created_by' => User::factory()->active()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(2),
        ]);
    }

    public function test_home_bundle_expands_when_nearby_empty_and_dedupes_worth_the_trip(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $alex = $this->city('Alexandria', 31.2001, 29.9187);

        $far = $this->publishedIn($alex);

        $user = User::factory()->active()->create([
            'city_id' => $obour->id,
            'distance_km' => 5,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/events/discover/home');
        $response->assertOk()
            ->assertJsonPath('meta.expanded', true)
            ->assertJsonPath('meta.distance_label', 'straight_line_km');

        $nearbyIds = collect($response->json('nearby'))->pluck('id');
        $worthIds = collect($response->json('worth_the_trip'))->pluck('id');

        $this->assertTrue($nearbyIds->contains($far->id));
        $this->assertFalse($worthIds->contains($far->id));
    }

    public function test_accepts_distance_100_and_all_sentinel(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $user = User::factory()->active()->create([
            'city_id' => $obour->id,
            'distance_km' => 25,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/events/discover/home?distance_km=100')->assertOk();
        $this->getJson('/api/events/discover/home?distance_km=0')->assertOk()
            ->assertJsonPath('meta.nationwide', true);
        $this->getJson('/api/events/discover/home?distance_km=7')->assertStatus(422);
    }

    public function test_device_origin_is_accepted_and_never_echoed_as_user_coords(): void
    {
        $obour = $this->city('Obour', 30.2285, 31.4769);
        $this->publishedIn($obour);

        $user = User::factory()->active()->create([
            'city_id' => $obour->id,
            'distance_km' => 50,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/events/discover/home?latitude=30.2285&longitude=31.4769&distance_km=50'
        );

        $response->assertOk()
            ->assertJsonPath('meta.origin_type', 'device')
            ->assertJsonPath('meta.distance_basis', 'device_to_event_or_city_center');

        $payload = json_encode($response->json());
        // Response must not expose a user GPS profile field.
        $this->assertStringNotContainsString('"user_latitude"', $payload);
        $this->assertStringNotContainsString('"user_longitude"', $payload);
    }

    public function test_rejects_invalid_device_coordinates(): void
    {
        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/events/discover/home?latitude=999&longitude=31')
            ->assertStatus(422);
    }
}
