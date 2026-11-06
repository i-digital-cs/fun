<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventQuickViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_load_quick_view_fragment(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Quick View Guest Event'])->fresh();

        $this->get(route('events.quick-view', $event))
            ->assertOk()
            ->assertSee('data-event-qv-panel', false)
            ->assertSee('Quick View Guest Event', false)
            ->assertSee(__('events.open_full_page'), false)
            ->assertDontSee('<html', false);
    }

    public function test_quick_view_includes_join_cta_for_authenticated_member(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Join From Overlay'])->fresh();
        $member = User::factory()->active()->create();

        $this->actingAs($member)
            ->get(route('events.quick-view', $event))
            ->assertOk()
            ->assertSee(__('events.request_to_join'), false);
    }

    public function test_guest_join_cta_points_at_login_with_return_to(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Login Return Overlay'])->fresh();
        $path = parse_url(event_show_url($event), PHP_URL_PATH);

        $this->get(route('events.quick-view', $event))
            ->assertOk()
            ->assertSee('returnTo='.urlencode((string) $path), false);
    }

    public function test_discover_listing_marks_quick_view_triggers(): void
    {
        $this->makePublishedEvent(['title' => 'Discover Card Trigger'])->fresh();

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('data-event-qv-list', false)
            ->assertSee('data-event-qv-open', false)
            ->assertSee('data-no-loader="1"', false)
            ->assertSee('id="event-quick-view"', false);
    }

    public function test_direct_canonical_url_still_renders_full_show_page(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Full Page Canonical'])->fresh();

        $this->get(event_show_url($event))
            ->assertOk()
            ->assertSee('Full Page Canonical', false)
            ->assertSee('Breadcrumb', false)
            ->assertDontSee('data-event-qv-panel', false);
    }

    public function test_login_return_to_sets_same_origin_intended(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Intended After Login'])->fresh();
        $path = parse_url(event_show_url($event), PHP_URL_PATH);

        $this->get(route('login', ['returnTo' => $path]))
            ->assertOk();

        $this->assertSame(url((string) $path), session('url.intended'));
    }

    public function test_login_return_to_rejects_external_host(): void
    {
        $this->get(route('login', ['returnTo' => 'https://evil.example/phish']))
            ->assertOk();

        $this->assertNull(session('url.intended'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePublishedEvent(array $overrides = []): Event
    {
        $host = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        return Event::factory()->published()->create(array_merge([
            'created_by' => $host->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
        ], $overrides));
    }
}
