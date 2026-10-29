<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReverseGeocodeApiTest extends TestCase
{
    public function test_reverse_geocode_returns_structured_label(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => '6th of October City, Giza, Egypt',
                'address' => [
                    'city' => '6th of October City',
                    'state' => 'Giza Governorate',
                    'country' => 'Egypt',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/places/reverse?'.http_build_query([
            'latitude' => 29.96,
            'longitude' => 30.92,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.short_name', '6th of October City')
            ->assertJsonPath('data.provider', 'nominatim');
        $this->assertStringContainsString('6th of October', (string) $response->json('data.display_name'));
    }

    public function test_reverse_geocode_validates_coordinates(): void
    {
        $this->getJson('/api/places/reverse?latitude=999&longitude=30')
            ->assertStatus(422);
    }

    public function test_reverse_geocode_provider_failure_returns_unavailable(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response('err', 500),
        ]);

        $response = $this->getJson('/api/places/reverse?'.http_build_query([
            'latitude' => 29.96,
            'longitude' => 30.92,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.display_name', null);
    }
}
