<?php

namespace Tests\Unit;

use App\Services\Location\ResolveDiscoveryOriginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveDiscoveryOriginPlaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_mode_accepts_place_coordinates_without_city_id(): void
    {
        $origins = app(ResolveDiscoveryOriginService::class);

        $origin = $origins->resolve(null, [
            'discovery_mode' => 'manual',
            'latitude' => 29.95,
            'longitude' => 30.91,
            'distance_km' => 25,
        ]);

        $this->assertSame('place', $origin['origin_type']);
        $this->assertSame(29.95, $origin['latitude']);
        $this->assertNull($origin['city']);
    }
}
