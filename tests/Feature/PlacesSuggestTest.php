<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlacesSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_suggest_returns_photon_results(): void
    {
        Http::fake([
            'photon.komoot.io/*' => Http::response([
                'features' => [
                    [
                        'geometry' => ['coordinates' => [31.2357, 30.0444]],
                        'properties' => [
                            'name' => 'Tahrir Square',
                            'city' => 'Cairo',
                            'country' => 'Egypt',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/places/suggest?q=tahrir')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Tahrir Square · Cairo · Egypt')
            ->assertJsonPath('data.0.latitude', 30.0444)
            ->assertJsonPath('data.0.longitude', 31.2357);
    }

    public function test_places_suggest_requires_auth_and_min_query(): void
    {
        $this->getJson('/api/places/suggest?q=ab')->assertUnauthorized();

        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/places/suggest?q=a')->assertUnprocessable();
    }

    public function test_places_suggest_accepts_city_bias(): void
    {
        Http::fake([
            'photon.komoot.io/*' => Http::response(['features' => []], 200),
        ]);

        $city = City::factory()->create([
            'latitude' => 30.0,
            'longitude' => 31.0,
        ]);
        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/places/suggest?q=cafe&city_id='.$city->id)
            ->assertOk()
            ->assertJsonPath('data', []);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'photon.komoot.io')
                && ($request['lat'] ?? null) == 30.0
                && ($request['lon'] ?? null) == 31.0;
        });
    }
}
