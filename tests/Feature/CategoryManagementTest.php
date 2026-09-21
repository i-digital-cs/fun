<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_deactivate_category(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/admin/categories', [
            'name_en' => 'Football',
            'name_ar' => 'كرة القدم',
            'sort_order' => 10,
        ]);

        $create->assertCreated();
        $id = $create->json('category.id');

        $this->putJson("/api/admin/categories/{$id}", [
            'name_en' => 'Football Match',
            'name_ar' => 'مباراة كرة قدم',
            'sort_order' => 5,
        ])->assertOk()->assertJsonPath('category.name_en', 'Football Match');

        $this->postJson("/api/admin/categories/{$id}/deactivate")
            ->assertOk()
            ->assertJsonPath('category.is_active', false);
    }

    public function test_public_categories_list_excludes_inactive(): void
    {
        $active = Category::factory()->create(['name_en' => 'Running', 'slug' => 'running', 'sort_order' => 1]);
        Category::factory()->inactive()->create(['slug' => 'hidden-cat']);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $ids = collect($response->json('categories'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertCount(1, $ids);
    }

    public function test_regular_user_cannot_manage_categories(): void
    {
        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/admin/categories', [
            'name_en' => 'Test',
            'name_ar' => 'اختبار',
        ])->assertForbidden();
    }

    public function test_admin_web_categories_index_renders(): void
    {
        Category::factory()->create(['name_en' => 'Cinema', 'slug' => 'cinema-web']);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee(__('catalog.categories'), false)
            ->assertSee('Cinema', false);
    }
}
