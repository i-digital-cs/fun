<?php

namespace Tests\Unit;

use App\Support\MapsUrlParser;
use Tests\TestCase;

class MapsUrlParserTest extends TestCase
{
    public function test_parses_google_maps_at_coords(): void
    {
        $parsed = app(MapsUrlParser::class)->parse(
            'https://www.google.com/maps/@30.0444200,31.2357120,17z'
        );

        $this->assertNotNull($parsed);
        $this->assertEqualsWithDelta(30.04442, $parsed['latitude'], 0.0001);
        $this->assertEqualsWithDelta(31.235712, $parsed['longitude'], 0.0001);
    }

    public function test_parses_google_query_coords(): void
    {
        $parsed = app(MapsUrlParser::class)->parse(
            'https://www.google.com/maps?q=25.2048,55.2708'
        );

        $this->assertNotNull($parsed);
        $this->assertEqualsWithDelta(25.2048, $parsed['latitude'], 0.0001);
        $this->assertEqualsWithDelta(55.2708, $parsed['longitude'], 0.0001);
    }

    public function test_parses_openstreetmap_link(): void
    {
        $parsed = app(MapsUrlParser::class)->parse(
            'https://www.openstreetmap.org/?mlat=30.05&mlon=31.24#map=16/30.05/31.24'
        );

        $this->assertNotNull($parsed);
        $this->assertEqualsWithDelta(30.05, $parsed['latitude'], 0.0001);
        $this->assertEqualsWithDelta(31.24, $parsed['longitude'], 0.0001);
    }

    public function test_returns_null_for_unparseable_url(): void
    {
        $this->assertNull(
            app(MapsUrlParser::class)->parse('https://example.com/about')
        );
    }
}
