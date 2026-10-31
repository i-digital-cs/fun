<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OrganicDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_explains_the_brand_in_the_initial_html(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>'.e(__('design.home_document_title')).'</title>', false)
            ->assertSee(__('design.home_meta_description'), false)
            ->assertSee(__('design.brand_explanation'), false)
            ->assertSee('"alternateName":["فانتي","فانيتي"', false)
            ->assertDontSee('<meta name="keywords"', false)
            ->assertSee('hreflang="ar"', false)
            ->assertSee('hreflang="en"', false)
            ->assertSee('hreflang="x-default"', false);
    }

    public function test_arabic_homepage_uses_arabic_metadata(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('<title>'.e(__('design.home_document_title', [], 'ar')).'</title>', false)
            ->assertSee(__('design.home_meta_description', [], 'ar'), false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('lang="ar"', false)
            ->assertSee('hreflang="ar"', false);
    }

    public function test_discover_page_describes_nearby_events(): void
    {
        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee(__('events.discover_document_title'), false)
            ->assertSee(__('events.discover_intro'), false)
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('<link rel="canonical" href="'.e(route('events.index')).'"', false);

        $this->get(route('events.index', ['q' => 'cairo']))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,follow"', false);
    }

    public function test_city_landing_is_published_only_with_enough_genuine_events(): void
    {
        $city = City::factory()->create(['name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'slug' => 'cairo']);
        $thin = City::factory()->create(['name_en' => 'Suez', 'name_ar' => 'السويس', 'slug' => 'suez']);
        $category = Category::factory()->create(['name_en' => 'Football', 'name_ar' => 'كرة', 'slug' => 'football']);

        foreach (range(1, 3) as $n) {
            $this->genuineEvent($city, $category, 'Cairo meetup '.$n);
        }
        $this->genuineEvent($thin, $category, 'Only one Suez meetup');
        $this->genuineEvent($thin, $category, 'Second Suez meetup');

        Cache::forget('seo.sitemap.xml');

        $this->get(route('events.city', $city))
            ->assertOk()
            ->assertSee('Events in Cairo', false)
            ->assertSee('القاهرة', false)
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('<link rel="canonical" href="'.e(route('events.city', $city)).'"', false)
            ->assertSee('"@type":"ItemList"', false);

        $this->get(route('events.city', $thin))->assertNotFound();

        $sitemap = $this->get(route('sitemap'));
        $sitemap->assertSee(route('events.city', $city), false);
        $sitemap->assertDontSee(route('events.city', $thin), false);
    }

    public function test_demo_and_hidden_events_do_not_create_a_landing_page(): void
    {
        $city = City::factory()->create(['slug' => 'demo-city']);
        $category = Category::factory()->create();

        foreach (range(1, 3) as $n) {
            $event = $this->genuineEvent($city, $category, 'Hidden '.$n);
            $event->forceFill([
                'description' => 'Join us for a seeded meetup that should stay out of landing pages.',
                'search_indexable' => false,
            ])->saveQuietly();
        }

        $this->get(route('events.city', $city))->assertNotFound();
    }

    public function test_activity_landing_and_second_page_canonical(): void
    {
        $city = City::factory()->create();
        $category = Category::factory()->create(['name_en' => 'Football', 'name_ar' => 'كرة', 'slug' => 'football']);

        foreach (range(1, 13) as $n) {
            $this->genuineEvent($city, $category, 'Football meetup '.$n);
        }

        $this->get(route('events.activity', $category))
            ->assertOk()
            ->assertSee('Football events', false)
            ->assertSee('name="robots" content="index,follow"', false);

        $this->get(route('events.activity', ['category' => $category, 'page' => 2]))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,follow"', false)
            ->assertSee('<link rel="canonical" href="'.e(route('events.activity', $category)).'"', false);
    }

    public function test_genuine_event_schema_uses_stored_place_date_and_capacity(): void
    {
        $host = User::factory()->active()->create(['name' => 'Nour Host']);
        $city = City::factory()->create(['name_en' => 'Giza', 'name_ar' => 'الجيزة']);
        $event = Event::factory()->published()->create([
            'created_by' => $host->id,
            'city_id' => $city->id,
            'title' => 'Five-a-side booking',
            'description' => 'A real five-a-side booking with neighbors at a known pitch.',
            'venue' => 'Local pitch',
            'max_participants' => 10,
            'cost_per_person' => 80,
        ]);

        $this->get(event_show_url($event))
            ->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"addressLocality":"Giza"', false)
            ->assertSee('"startDate":"'.$event->starts_at->toIso8601String().'"', false)
            ->assertSee('"name":"Nour Host"', false)
            ->assertSee('"maximumAttendeeCapacity":10', false)
            ->assertSee('"priceCurrency":"EGP"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->assertSee('hreflang="ar"', false)
            ->assertDontSee('<meta name="keywords"', false);
    }

    private function genuineEvent(City $city, Category $category, string $title): Event
    {
        return Event::factory()->published()->create([
            'city_id' => $city->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => 'Neighbors meet for a local activity with a real place and a real plan.',
        ]);
    }
}
