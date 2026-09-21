<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserLocationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_set_city_and_distance_preference(): void
    {
        $city = City::factory()->create(['name_en' => 'Obour', 'slug' => 'obour']);
        $user = User::factory()->active()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/settings/location', [
            'city_id' => $city->id,
            'distance_km' => 25,
        ])->assertOk()
            ->assertJsonPath('user.city_id', $city->id)
            ->assertJsonPath('user.distance_km', 25);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'city_id' => $city->id,
            'distance_km' => 25,
        ]);
    }

    public function test_user_cannot_select_inactive_city_or_invalid_distance(): void
    {
        $city = City::factory()->inactive()->create();
        $user = User::factory()->active()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/settings/location', [
            'city_id' => $city->id,
            'distance_km' => 25,
        ])->assertStatus(422);

        $active = City::factory()->create();

        $this->putJson('/api/settings/location', [
            'city_id' => $active->id,
            'distance_km' => 15,
        ])->assertStatus(422);
    }

    public function test_web_location_settings_page_renders(): void
    {
        City::factory()->create(['name_en' => 'Cairo', 'slug' => 'cairo-web']);
        $user = User::factory()->active()->create();

        $this->actingAs($user)
            ->get(route('settings.location.edit'))
            ->assertOk()
            ->assertSee(__('location.location_settings'), false);
    }
}
