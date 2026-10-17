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
        $response->assertSee('<title>'.e(__('design.home_document_title')).'</title>', false);
        $response->assertSee(__('design.home_meta_description'), false);
        $response->assertSee(__('design.brand_identity'), false);
        $response->assertSee('"email":"'.__('privacy.contact_email').'"', false);
        $response->assertDontSee('"sameAs"', false);
        $response->assertDontSee('<meta name="keywords"', false);
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
        $response->assertSee('"addressLocality":"'.$event->city->name_en.'"', false);
        $response->assertSee('"startDate":"'.$event->starts_at->toIso8601String().'"', false);
        $response->assertSee('Friday Football Meetup', false);
        $response->assertSee(event_show_url($event), false);
        $response->assertSee('<link rel="canonical" href="'.e(event_show_url($event)).'"', false);
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

    public function test_privacy_policy_page_is_indexable(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee(__('privacy.title'), false)
            ->assertSee(__('privacy.meta_description'), false);
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
        $response->assertSee(route('legal.privacy'), false);
        $response->assertSee(event_show_url($event), false);
        $response->assertDontSee('Sitemap Event', false);

        $xml = simplexml_load_string($response->getContent());
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $home = $xml->xpath('//s:url[s:loc="'.route('home').'"]');
        $this->assertNotEmpty($home);
        $this->assertCount(0, $home[0]->lastmod, 'Static URLs omit lastmod when no content timestamp exists.');

        $event->forceFill(['updated_at' => '2026-01-15 12:00:00'])->saveQuietly();
        Cache::forget('seo.sitemap.xml');
        $again = $this->get(route('sitemap'));
        $againXml = simplexml_load_string($again->getContent());
        $this->assertInstanceOf(\SimpleXMLElement::class, $againXml);
        $againXml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $eventNode = $againXml->xpath('//s:url[s:loc="'.event_show_url($event->fresh()).'"]');
        $this->assertNotEmpty($eventNode);
        $this->assertStringStartsWith('2026-01-15', (string) $eventNode[0]->lastmod);
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
