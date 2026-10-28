<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use App\Support\UserPublicId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_page_shows_safe_data(): void
    {
        $user = User::factory()->active()->create([
            'name' => 'Host Person',
            'public_id' => 'Funity-F001007',
        ]);
        $city = City::factory()->create();
        $category = Category::factory()->create();

        Event::factory()->published()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'title' => 'Public Hosted Event',
        ]);

        $this->get(route('users.public.show', 'Funity-F001007'))
            ->assertOk()
            ->assertSee('Host Person')
            ->assertSee('Funity-F001007')
            ->assertSee('Public Hosted Event')
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('"@type":"Person"', false)
            ->assertDontSee($user->email);
    }

    public function test_public_profiles_stay_indexable_unless_the_owner_flag_is_off(): void
    {
        $user = User::factory()->active()->create([
            'name' => 'Indexed Neighbor',
            'public_id' => 'Funity-F001008',
        ]);

        config(['seo.public_profiles_indexable' => false]);

        $this->get(route('users.public.show', $user->public_id))
            ->assertOk()
            ->assertSee('Indexed Neighbor')
            ->assertSee('name="robots" content="noindex,follow"', false)
            ->assertDontSee('"@type":"Person"', false)
            ->assertDontSee($user->email);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('/u/'.$user->public_id, false);
    }

    public function test_api_public_profile_returns_payload(): void
    {
        $user = User::factory()->active()->create([
            'public_id' => 'Funity-F001100',
            'name' => 'Api Host',
        ]);

        $this->getJson('/api/users/Funity-F001100')
            ->assertOk()
            ->assertJsonPath('data.public_id', 'Funity-F001100')
            ->assertJsonPath('data.name', 'Api Host')
            ->assertJsonMissingPath('data.email');
    }

    public function test_deactivated_user_public_profile_is_not_found(): void
    {
        User::factory()->deactivated()->create([
            'public_id' => 'Funity-F001999',
        ]);

        $this->get(route('users.public.show', 'Funity-F001999'))->assertNotFound();
        $this->getJson('/api/users/Funity-F001999')->assertNotFound();
    }

    public function test_me_includes_public_id(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('public_id', $user->public_id);
    }

    public function test_public_ids_are_sequential_from_start(): void
    {
        $first = User::factory()->active()->create();
        $second = User::factory()->active()->create();

        $this->assertSame(UserPublicId::format(UserPublicId::START_NUMBER), $first->public_id);
        $this->assertSame(UserPublicId::format(UserPublicId::START_NUMBER + 1), $second->public_id);
        $this->assertTrue(UserPublicId::isValid($first->public_id));
    }
}
