<?php

namespace Tests\Unit;

use App\Support\GeoDistance;
use Tests\TestCase;

class GeoDistanceTest extends TestCase
{
    public function test_distance_between_nearby_points_is_within_expected_range(): void
    {
        // Roughly Cairo center to a nearby point (~11 km-ish scale check)
        $km = GeoDistance::kilometers(30.0444, 31.2357, 30.0800, 31.3000);

        $this->assertGreaterThan(5, $km);
        $this->assertLessThan(20, $km);
        $this->assertTrue(GeoDistance::isWithinKm(30.0444, 31.2357, 30.0800, 31.3000, 25));
        $this->assertFalse(GeoDistance::isWithinKm(30.0444, 31.2357, 30.0800, 31.3000, 5));
    }
}
