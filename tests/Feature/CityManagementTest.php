<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_deactivate_city(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/admin/cities', [
            'name_en' => 'Obour',
            'name_ar' => 'العبور',
            'latitude' => 30.2285,
            'longitude' => 31.4769,
        ]);

        $create->assertCreated();
        $cityId = $create->json('city.id');

        $this->postJson("/api/admin/cities/{$cityId}/deactivate")
            ->assertOk()
            ->assertJsonPath('city.is_active', false);

        $this->assertDatabaseHas('cities', [
            'id' => $cityId,
            'is_active' => false,
        ]);
    }

    public function test_active_cities_are_listed_publicly(): void
    {
        $active = City::factory()->create(['name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'slug' => 'cairo']);
        City::factory()->inactive()->create(['slug' => 'hidden-city']);

        $response = $this->getJson('/api/cities');

        $response->assertOk();
        $ids = collect($response->json('cities'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertSame([5, 10, 25, 50], $response->json('distances_km'));
    }

    public function test_regular_user_cannot_manage_cities(): void
    {
        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/admin/cities', [
            'name_en' => 'Test',
            'name_ar' => 'اختبار',
            'latitude' => 30,
            'longitude' => 31,
        ])->assertForbidden();
    }
}
