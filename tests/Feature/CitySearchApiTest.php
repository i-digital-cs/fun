<?php

namespace Tests\Feature;

use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitySearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_english_aliases_for_6th_of_october(): void
    {
        $october = City::factory()->create([
            'name_en' => '6th of October City',
            'name_ar' => 'مدينة السادس من أكتوبر',
            'slug' => '6th-of-october-city',
            'latitude' => 29.9381,
            'longitude' => 30.9138,
        ]);
        City::factory()->create([
            'name_en' => 'Alexandria',
            'name_ar' => 'الإسكندرية',
            'slug' => 'alexandria',
            'latitude' => 31.2001,
            'longitude' => 29.9187,
        ]);

        $response = $this->getJson('/api/cities/search?q=6%20October');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($october->id));
        $this->assertSame('city', $response->json('data.0.source'));
        $this->assertArrayHasKey('distances_km', $response->json());
    }

    public function test_search_matches_arabic_october_alias(): void
    {
        $october = City::factory()->create([
            'name_en' => '6th of October City',
            'name_ar' => 'مدينة السادس من أكتوبر',
            'slug' => '6th-of-october-city',
            'latitude' => 29.9381,
            'longitude' => 30.9138,
        ]);

        $response = $this->getJson('/api/cities/search?q='.urlencode('٦ أكتوبر'));
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($october->id));
    }

    public function test_empty_query_returns_active_cities(): void
    {
        City::factory()->count(3)->create();

        $this->getJson('/api/cities/search')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'latitude', 'longitude', 'subtitle', 'source']]]);
    }
}
