<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventJoinRequest;
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
        $response->assertSee(__('events.nearby_events'), false);
        $response->assertSee(__('home.all_activities'), false);
        $response->assertSee('Football', false);
        $response->assertSee(__('home.gather_title'), false);
        $response->assertSee(__('home.trust_center'), false);
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
            ->assertSee(__('home.trust_title'));
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

    public function test_home_shows_active_membership_and_pending_request(): void
    {
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $owner = User::factory()->active()->create(['name' => 'Host User']);
        $member = User::factory()->active()->create();

        $joinedEvent = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Joined Kickabout',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(2),
        ]);
        $joinedEvent->members()->create([
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        $pendingEvent = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Pending Board Games',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(3),
        ]);
        EventJoinRequest::query()->create([
            'event_id' => $pendingEvent->id,
            'user_id' => $member->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('home.your_participations'), false)
            ->assertSee(__('home.confirmed_attendance'), false)
            ->assertSee('Joined Kickabout', false)
            ->assertSee(__('home.request_pending'), false)
            ->assertSee('Pending Board Games', false)
            ->assertSee(__('home.open_chat'), false);
    }

    public function test_guest_home_does_not_show_participations_strip(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('home.your_participations'), false);
    }

    public function test_home_shows_trust_block_and_safety_guidelines_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('home.trust_title'))
            ->assertSee(__('identity.help'), false)
            ->assertSee(__('home.safety_guidelines'), false)
            ->assertSee(route('safety.guidelines'), false);

        $this->get(route('safety.guidelines'))
            ->assertOk()
            ->assertSee(__('safety.guidelines_title'), false)
            ->assertSee(__('safety.guidelines_disclaimer'), false);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
