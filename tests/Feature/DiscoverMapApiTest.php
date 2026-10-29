<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverMapApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_returns_exact_markers_inside_bounds(): void
    {
        $city = City::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => 30.05,
            'longitude' => 31.24,
            'title' => 'Map Pin Meetup',
            'starts_at' => now()->addDays(2),
        ]);

        // Far outside bounds
        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => 31.2,
            'longitude' => 29.9,
            'starts_at' => now()->addDays(3),
        ]);

        $response = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.2,
            'south' => 29.9,
            'east' => 31.4,
            'west' => 31.0,
            'zoom' => 12,
        ]));

        $response->assertOk();
        $ids = collect($response->json('markers'))->pluck('id');
        $this->assertTrue($ids->contains($event->id));
        $this->assertSame('exact', collect($response->json('markers'))->firstWhere('id', $event->id)['location_precision']);
    }

    public function test_rejects_oversized_bbox_at_city_zoom(): void
    {
        $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 32,
            'south' => 22,
            'east' => 36,
            'west' => 25,
            'zoom' => 12,
        ]))->assertStatus(422);
    }

    /**
     * Regression: initial Discover nationwide viewport must not 422.
     *
     * @dataProvider nationwideZoomProvider
     */
    public function test_egypt_wide_viewport_clamps_at_low_zoom(int $zoom): void
    {
        $city = City::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'name_en' => 'Cairo',
        ]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => null,
            'longitude' => null,
            'starts_at' => now()->addDays(2),
        ]);

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => 30.05,
            'longitude' => 31.24,
            'starts_at' => now()->addDays(2),
        ]);

        // Typical Leaflet Egypt-wide canvas (larger than previous hard caps).
        $response = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 33.5,
            'south' => 20.4,
            'east' => 38.0,
            'west' => 22.0,
            'zoom' => $zoom,
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.bounds_clamped', true)
            ->assertJsonPath('meta.nationwide_mode', true);

        $this->assertGreaterThanOrEqual(1, count($response->json('city_aggregates') ?? []));
        $this->assertSame(
            21.5,
            (float) $response->json('meta.bounds.south')
        );
        $this->assertSame(
            32.0,
            (float) $response->json('meta.bounds.north')
        );
    }

    public static function nationwideZoomProvider(): array
    {
        return [
            'zoom_4' => [4],
            'zoom_5' => [5],
            'zoom_6' => [6],
        ];
    }

    public function test_past_events_excluded_from_map(): void
    {
        $city = City::factory()->create([
            'latitude' => 30.05,
            'longitude' => 31.24,
        ]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        $past = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => 30.05,
            'longitude' => 31.24,
            'starts_at' => now()->subDays(5),
        ]);

        $upcoming = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => 30.051,
            'longitude' => 31.241,
            'starts_at' => now()->addDays(2),
        ]);

        $response = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.2,
            'south' => 29.9,
            'east' => 31.4,
            'west' => 31.0,
            'zoom' => 12,
        ]));

        $response->assertOk();
        $ids = collect($response->json('markers'))->pluck('id');
        $this->assertTrue($ids->contains($upcoming->id));
        $this->assertFalse($ids->contains($past->id));
    }

    public function test_city_aggregates_do_not_double_count_exact_events(): void
    {
        $city = City::factory()->create([
            'latitude' => 29.9285,
            'longitude' => 30.9188,
        ]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => 29.93,
            'longitude' => 30.92,
            'starts_at' => now()->addDays(2),
        ]);

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => null,
            'longitude' => null,
            'starts_at' => now()->addDays(2),
        ]);

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => null,
            'longitude' => null,
            'starts_at' => now()->addDays(3),
        ]);

        $response = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.2,
            'south' => 29.7,
            'east' => 31.2,
            'west' => 30.7,
            'zoom' => 12,
            'include_approximate' => 0,
        ]));

        $response->assertOk();
        $this->assertSame(1, (int) $response->json('meta.point_count'));
        $agg = collect($response->json('city_aggregates'))->firstWhere('city_id', $city->id);
        $this->assertNotNull($agg);
        $this->assertSame(2, $agg['count']);
    }

    public function test_approximate_city_markers_opt_in(): void
    {
        $city = City::factory()->create([
            'latitude' => 29.9285,
            'longitude' => 30.9188,
        ]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'latitude' => null,
            'longitude' => null,
            'starts_at' => now()->addDays(2),
        ]);

        $without = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.2,
            'south' => 29.7,
            'east' => 31.2,
            'west' => 30.7,
            'zoom' => 12,
            'include_approximate' => 0,
        ]));
        $without->assertOk();
        $this->assertFalse(collect($without->json('markers'))->pluck('id')->contains($event->id));
        $agg = collect($without->json('city_aggregates'))->firstWhere('city_id', $city->id);
        $this->assertNotNull($agg);
        $this->assertSame('city_level', $agg['location_precision']);
        $this->assertSame(1, $agg['count']);

        $with = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.2,
            'south' => 29.7,
            'east' => 31.2,
            'west' => 30.7,
            'zoom' => 12,
            'include_approximate' => 1,
        ]));
        $with->assertOk();
        $row = collect($with->json('markers'))->firstWhere('id', $event->id);
        $this->assertNotNull($row);
        $this->assertSame('approximate', $row['location_precision']);
    }

    public function test_low_zoom_returns_clusters(): void
    {
        $city = City::factory()->create(['latitude' => 30.05, 'longitude' => 31.24]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        foreach (range(1, 5) as $i) {
            Event::factory()->published()->create([
                'created_by' => $owner->id,
                'category_id' => $category->id,
                'city_id' => $city->id,
                'status' => EventStatus::Published,
                'latitude' => 30.05 + ($i * 0.001),
                'longitude' => 31.24 + ($i * 0.001),
                'starts_at' => now()->addDays($i),
            ]);
        }

        $response = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.2,
            'south' => 29.9,
            'east' => 31.4,
            'west' => 31.0,
            'zoom' => 6,
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.clustered', true);
        $this->assertNotEmpty($response->json('clusters'));
    }

    public function test_empty_area_returns_empty_collections(): void
    {
        $response = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.01,
            'south' => 30.00,
            'east' => 31.01,
            'west' => 31.00,
            'zoom' => 14,
        ]));

        $response->assertOk();
        $this->assertSame([], $response->json('markers'));
        $this->assertSame([], $response->json('clusters'));
        $this->assertSame([], $response->json('city_aggregates'));
        $this->assertSame(0, (int) $response->json('meta.point_count'));
    }

    public function test_low_zoom_clusters_nearby_city_aggregates_without_double_count(): void
    {
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        $cairo = City::factory()->create(['latitude' => 30.0444, 'longitude' => 31.2357]);
        $giza = City::factory()->create(['latitude' => 30.0131, 'longitude' => 31.2089]);

        foreach ([$cairo, $giza] as $city) {
            foreach (range(1, 3) as $i) {
                Event::factory()->published()->create([
                    'created_by' => $owner->id,
                    'category_id' => $category->id,
                    'city_id' => $city->id,
                    'status' => EventStatus::Published,
                    'latitude' => null,
                    'longitude' => null,
                    'starts_at' => now()->addDays($i),
                ]);
            }
        }

        $low = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 31.5,
            'south' => 22.0,
            'east' => 35.0,
            'west' => 25.0,
            'zoom' => 5,
        ]));
        $low->assertOk();
        $aggs = collect($low->json('city_aggregates'));
        $this->assertTrue(
            $aggs->contains(fn ($a) => ($a['type'] ?? '') === 'city_cluster')
            || $aggs->count() <= 2
        );
        $total = $aggs->sum(fn ($a) => (int) ($a['count'] ?? 0));
        $this->assertSame(6, $total);

        $high = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.2,
            'south' => 29.9,
            'east' => 31.4,
            'west' => 31.0,
            'zoom' => 12,
        ]));
        $high->assertOk();
        $this->assertCount(2, $high->json('city_aggregates'));
        $this->assertFalse(
            collect($high->json('city_aggregates'))->contains(fn ($a) => ($a['type'] ?? '') === 'city_cluster')
        );
    }

    public function test_origin_aware_map_includes_city_aggregate_outside_tight_viewport(): void
    {
        config(['location.city_location_uncertainty_km' => 10]);
        config(['location.progressive_radii_km' => [50, 100, 200, 0]]);

        $zagazig = City::factory()->create([
            'name_en' => 'Zagazig',
            'latitude' => 30.5877,
            'longitude' => 31.5020,
        ]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $zagazig->id,
            'status' => EventStatus::Published,
            'latitude' => null,
            'longitude' => null,
            'starts_at' => now()->addDays(2),
        ]);

        // Tight viewport around Al Ibrahimeya that excludes Zagazig centroid.
        $response = $this->getJson('/api/discovery/map?'.http_build_query([
            'north' => 30.76,
            'south' => 30.70,
            'east' => 31.63,
            'west' => 31.56,
            'zoom' => 12,
            'discovery_mode' => 'device',
            'latitude' => 30.729,
            'longitude' => 31.598,
            'distance_km' => 25,
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.origin_aware', true)
            ->assertJsonPath('meta.is_suggestion', true)
            ->assertJsonPath('meta.effective_radius_km', 50);

        $agg = collect($response->json('city_aggregates'))->firstWhere('city_id', $zagazig->id);
        $this->assertNotNull($agg);
        $this->assertSame(1, (int) $agg['count']);
    }

    public function test_city_events_endpoint_returns_city_events(): void
    {
        $city = City::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'name_en' => 'Cairo Test',
        ]);
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();

        $cityOnly = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'City Only Meetup',
            'status' => EventStatus::Published,
            'latitude' => null,
            'longitude' => null,
            'starts_at' => now()->addDays(2),
        ]);
        $exact = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Exact Pin Meetup',
            'status' => EventStatus::Published,
            'latitude' => 30.05,
            'longitude' => 31.24,
            'starts_at' => now()->addDays(3),
        ]);

        $response = $this->getJson('/api/discovery/city-events?city_id='.$city->id);
        $response->assertOk();
        $this->assertSame($city->id, (int) $response->json('city.id'));
        $ids = collect($response->json('events'))->pluck('id');
        $this->assertTrue($ids->contains($cityOnly->id));
        $this->assertTrue($ids->contains($exact->id));
        $cityRow = collect($response->json('events'))->firstWhere('id', $cityOnly->id);
        $this->assertFalse($cityRow['has_exact_coordinates']);
        $exactRow = collect($response->json('events'))->firstWhere('id', $exact->id);
        $this->assertTrue($exactRow['has_exact_coordinates']);
    }
}
