<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_requires_city(): void
    {
        $user = User::factory()->active()->create(['city_id' => null]);
        Sanctum::actingAs($user);

        $this->getJson('/api/weather')
            ->assertStatus(422)
            ->assertJsonPath('data', null);
    }

    public function test_weather_returns_forecast_for_user_city(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'timezone' => 'Africa/Cairo',
                'current' => [
                    'time' => '2026-09-23T14:00',
                    'temperature_2m' => 36.2,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'time' => ['2026-09-23', '2026-09-24', '2026-09-25', '2026-09-26', '2026-09-27'],
                    'weather_code' => [0, 3, 61, 0, 2],
                    'temperature_2m_max' => [42, 38, 30, 35, 33],
                    'temperature_2m_min' => [28, 26, 22, 24, 23],
                ],
            ], 200),
        ]);

        $city = City::factory()->create([
            'name_en' => 'Cairo',
            'name_ar' => 'القاهرة',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'is_active' => true,
        ]);
        $user = User::factory()->active()->create(['city_id' => $city->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.city.id', $city->id)
            ->assertJsonPath('data.temperature', 36)
            ->assertJsonPath('data.condition', 'clear')
            ->assertJsonPath('data.range', '42°/28°')
            ->assertJsonPath('data.forecast.0.condition', 'overcast')
            ->assertJsonPath('data.forecast.1.condition', 'rain');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.open-meteo.com'));
    }

    public function test_dashboard_shows_weather_card_for_city_user(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'timezone' => 'Africa/Cairo',
                'current' => [
                    'time' => '2026-09-23T14:00',
                    'temperature_2m' => 30,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'time' => ['2026-09-23', '2026-09-24', '2026-09-25', '2026-09-26', '2026-09-27'],
                    'weather_code' => [0, 0, 0, 0, 0],
                    'temperature_2m_max' => [33, 32, 31, 30, 29],
                    'temperature_2m_min' => [22, 21, 20, 19, 18],
                ],
            ], 200),
        ]);

        $city = City::factory()->create([
            'name_en' => 'Obour',
            'latitude' => 30.2,
            'longitude' => 31.5,
            'is_active' => true,
        ]);
        $user = User::factory()->active()->create(['city_id' => $city->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('weather-card', false)
            ->assertSee('Obour', false)
            ->assertSee('30°', false);
    }

    public function test_home_shows_weather_card(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'timezone' => 'Africa/Cairo',
                'current' => [
                    'time' => '2026-09-23T14:00',
                    'temperature_2m' => 34,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'time' => ['2026-09-23', '2026-09-24', '2026-09-25', '2026-09-26', '2026-09-27'],
                    'weather_code' => [0, 0, 0, 0, 0],
                    'temperature_2m_max' => [36, 35, 34, 33, 32],
                    'temperature_2m_min' => [24, 23, 22, 21, 20],
                ],
            ], 200),
        ]);

        City::factory()->create([
            'name_en' => 'Cairo',
            'latitude' => 30.04,
            'longitude' => 31.23,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('weather-card', false)
            ->assertSee(__('weather.section_title'), false)
            ->assertSee('34°', false);
    }
}
