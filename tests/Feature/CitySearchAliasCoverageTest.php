<?php

namespace Tests\Feature;

use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitySearchAliasCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        City::factory()->create([
            'name_en' => '6th of October',
            'name_ar' => 'السادس من أكتوبر',
            'slug' => '6th-of-october',
        ]);
        City::factory()->create([
            'name_en' => 'Sheikh Zayed',
            'name_ar' => 'الشيخ زايد',
            'slug' => 'sheikh-zayed',
        ]);
        City::factory()->create([
            'name_en' => 'New Cairo',
            'name_ar' => 'القاهرة الجديدة',
            'slug' => 'new-cairo',
        ]);
    }

    /**
     * @dataProvider searchableQueries
     */
    public function test_supported_search_queries(string $query, string $expectedNameEn): void
    {
        $response = $this->getJson('/api/cities/search?q='.urlencode($query));
        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name_en');
        $this->assertTrue(
            $names->contains($expectedNameEn),
            "Expected {$expectedNameEn} for query [{$query}], got: ".$names->implode(', ')
        );
    }

    public static function searchableQueries(): array
    {
        return [
            '6 October' => ['6 October', '6th of October'],
            '6th of October' => ['6th of October', '6th of October'],
            'arabic october' => ['٦ أكتوبر', '6th of October'],
            'sheikh zayed ar' => ['الشيخ زايد', 'Sheikh Zayed'],
            'New Cairo' => ['New Cairo', 'New Cairo'],
            'fifth settlement alias' => ['التجمع الخامس', 'New Cairo'],
        ];
    }
}
