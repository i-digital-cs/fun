<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_successful_response(): void
    {
        Category::factory()->create(['name_en' => 'Football', 'name_ar' => 'كرة القدم', 'is_active' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(site_display_name(), false);
        $response->assertSee(__('home.headline'), false);
        $response->assertSee(__('identity.help'), false);
        $response->assertSee(__('events.nearby_events'), false);
        $response->assertSee(__('home.all_activities'), false);
        $response->assertSee('Football', false);
    }

    public function test_home_header_locale_toggle_is_present(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('name="locale"', false)
            ->assertSee('value="en"', false)
            ->assertSee('value="ar"', false);
    }

    public function test_authenticated_home_shows_welcome_back(): void
    {
        $city = City::factory()->create(['name_en' => 'Obour', 'name_ar' => 'العبور']);
        $user = User::factory()->create([
            'name' => 'Kareem',
            'status' => AccountStatus::Active,
            'city_id' => $city->id,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('home.welcome_back', ['name' => 'Kareem']), false)
            ->assertSee(__('home.active_in_city', ['city' => 'Obour']), false)
            ->assertDontSee(__('identity.help'), false);
    }

    public function test_home_shows_published_event_cards(): void
    {
        $city = City::factory()->create(['name_en' => 'Obour', 'name_ar' => 'العبور']);
        $category = Category::factory()->create(['name_en' => 'Football', 'is_active' => true]);
        $owner = User::factory()->active()->create();

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Evening Match Obour',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDay(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Evening Match Obour', false)
            ->assertSee(__('events.view_event'), false)
            ->assertSee(__('home.happening'), false);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
