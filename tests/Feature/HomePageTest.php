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
        $response->assertSee(__('design.welcome_guest'), false);
        $response->assertSee(__('design.nav_nearby_short'), false);
        $response->assertSee(__('home.all_activities'), false);
        $response->assertSee('Football', false);
        $response->assertSee(__('design.gather_title'), false);
        $response->assertSee(__('events.discover'), false);
        $response->assertSee(__('home.safety_guidelines'), false);
    }

    public function test_home_header_locale_toggle_is_present(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('name="locale"', false)
            ->assertSee('value="ar"', false);
    }

    public function test_authenticated_home_still_shows_marketing_shell(): void
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
            ->assertSee(__('design.gather_title'), false)
            ->assertDontSee(__('dashboard.greeting_sub'), false)
            ->assertSee(__('ui.dashboard'), false);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_dashboard_shows_member_shell(): void
    {
        $city = City::factory()->create(['name_en' => 'Obour', 'name_ar' => 'العبور']);
        $user = User::factory()->create([
            'name' => 'Kareem',
            'status' => AccountStatus::Active,
            'city_id' => $city->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.greeting_sub'), false)
            ->assertSee(__('dashboard.nearby_title'), false)
            ->assertSee(__('events.create_event'), false)
            ->assertSee('Kareem', false)
            ->assertSee('Obour', false)
            ->assertSee(__('dashboard.safety_title'), false);
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
            ->assertSee(__('design.discover_title_generic'), false);
    }

    public function test_authenticated_dashboard_shows_nearby_event_cards(): void
    {
        $city = City::factory()->create(['name_en' => 'Obour', 'name_ar' => 'العبور']);
        $category = Category::factory()->create(['name_en' => 'Football', 'is_active' => true]);
        $owner = User::factory()->active()->create();
        $user = User::factory()->active()->create(['city_id' => $city->id]);

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Evening Match Obour',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Evening Match Obour', false)
            ->assertSee(__('events.view_event'), false)
            ->assertSee(__('dashboard.nearby_title'), false);
    }

    public function test_guest_home_does_not_show_dashboard_greeting(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('dashboard.greeting_sub'), false);
    }

    public function test_home_shows_trust_block_and_safety_guidelines_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('design.trust_title'))
            ->assertSee(__('design.trust_body'), false)
            ->assertSee(__('home.safety_guidelines'), false)
            ->assertSee(route('safety.guidelines'), false);

        $this->get(route('safety.guidelines'))
            ->assertOk()
            ->assertSee(__('safety.guidelines_title'), false)
            ->assertSee(__('safety.guidelines_disclaimer'), false);
    }

    public function test_authenticated_dashboard_shows_safety_guidelines_link(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.safety_cta'), false)
            ->assertSee(route('safety.guidelines'), false);
    }

    public function test_dashboard_falls_back_to_nationwide_when_nearby_empty(): void
    {
        $nearCity = City::factory()->create([
            'name_en' => 'Near City',
            'latitude' => 30.0,
            'longitude' => 31.0,
        ]);
        $farCity = City::factory()->create([
            'name_en' => 'Far City',
            'latitude' => 10.0,
            'longitude' => 10.0,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $owner = User::factory()->active()->create();
        $user = User::factory()->active()->create([
            'city_id' => $nearCity->id,
            'distance_km' => 10,
        ]);

        Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $farCity->id,
            'title' => 'Far Away Meetup',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Far Away Meetup', false)
            ->assertSee(__('events.nationwide_notice'), false);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
