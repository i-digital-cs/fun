<?php

namespace Tests\Unit;

use App\Models\City;
use App\Models\User;
use App\Services\Location\ResolveDiscoveryOriginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResolveDiscoveryOriginServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResolveDiscoveryOriginService $origins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->origins = app(ResolveDiscoveryOriginService::class);
    }

    public function test_manual_mode_uses_city_center(): void
    {
        $city = City::factory()->create([
            'latitude' => 30.0,
            'longitude' => 31.0,
        ]);

        $origin = $this->origins->resolve(null, [
            'discovery_mode' => 'manual',
            'city_id' => $city->id,
            'distance_km' => 25,
            'latitude' => 29.0,
            'longitude' => 30.0,
        ]);

        $this->assertSame('city', $origin['origin_type']);
        $this->assertSame(30.0, $origin['latitude']);
        $this->assertTrue($origin['city']->is($city));
    }

    public function test_device_mode_requires_coords_and_ignores_city(): void
    {
        $city = City::factory()->create([
            'latitude' => 30.0,
            'longitude' => 31.0,
        ]);

        $origin = $this->origins->resolve(null, [
            'discovery_mode' => 'device',
            'city_id' => $city->id,
            'latitude' => 29.5,
            'longitude' => 30.5,
            'distance_km' => 10,
        ]);

        $this->assertSame('device', $origin['origin_type']);
        $this->assertSame(29.5, $origin['latitude']);
        $this->assertNull($origin['city']);
    }

    public function test_nationwide_mode_returns_null_even_with_profile_city(): void
    {
        $city = City::factory()->create();
        $user = User::factory()->active()->create(['city_id' => $city->id]);

        $this->assertNull($this->origins->resolve($user, [
            'discovery_mode' => 'nationwide',
            'distance_km' => 0,
        ]));
    }

    public function test_device_mode_without_coords_fails_validation(): void
    {
        $this->expectException(ValidationException::class);

        $this->origins->resolve(null, [
            'discovery_mode' => 'device',
            'distance_km' => 25,
        ]);
    }
}
