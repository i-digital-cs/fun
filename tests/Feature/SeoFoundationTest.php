<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_emits_meta_open_graph_and_json_ld(): void
    {
        Category::factory()->create(['is_active' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:image"', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('name="robots" content="index,follow"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"@type":"Organization"', false);
    }

    public function test_event_show_emits_event_json_ld_and_index_robots(): void
    {
        $event = $this->makePublishedEvent([
            'title' => 'Friday Football Meetup',
            'description' => 'Casual 7-a-side for neighbors who want a fun game.',
        ]);

        $response = $this->get(event_show_url($event));

        $response->assertOk();
        $response->assertSee('Friday Football Meetup', false);
        $response->assertSee('name="robots" content="index,follow"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"Event"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('Friday Football Meetup', false);
        $response->assertSee(event_show_url($event), false);
    }

    public function test_discover_page_is_indexable(): void
    {
        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee(route('events.index'), false);
    }

    public function test_safety_page_is_indexable(): void
    {
        $this->get(route('safety.guidelines'))
            ->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee(__('safety.guidelines_intro'), false);
    }

    public function test_login_is_noindex(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);
    }

    public function test_dashboard_is_noindex(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);
    }

    public function test_robots_txt_disallows_private_areas_and_lists_sitemap(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Disallow: /dashboard', false);
        $response->assertSee('Allow: /events', false);
        $response->assertSee('Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml', false);
    }

    public function test_sitemap_includes_public_routes_and_published_events(): void
    {
        Cache::forget('seo.sitemap.xml');

        $event = $this->makePublishedEvent(['title' => 'Sitemap Event']);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('events.index'), false);
        $response->assertSee(route('safety.guidelines'), false);
        $response->assertSee(event_show_url($event), false);
        $response->assertDontSee('Sitemap Event', false);
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
