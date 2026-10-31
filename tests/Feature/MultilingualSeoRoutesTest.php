<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use App\Support\LocaleUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MultilingualSeoRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_homepage_is_crawlable_without_session(): void
    {
        $response = $this->get('/ar');
        $response->assertOk();
        $response->assertSee('lang="ar"', false);
        $response->assertSee('hreflang="ar"', false);
        $response->assertSee('hreflang="en"', false);
        $response->assertSee('hreflang="x-default"', false);
        $this->assertStringContainsString('/ar"', $response->getContent());
    }

    public function test_english_prefix_redirects_to_bare_canonical(): void
    {
        $this->get('/en')->assertRedirect(route('home'));
        $this->get('/en/events')->assertRedirect(route('events.index'));
    }

    public function test_published_event_has_reciprocal_hreflang_and_arabic_route(): void
    {
        $owner = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'title' => 'Cairo Sunset Meetup',
            'status' => EventStatus::Published,
            'search_indexable' => true,
        ]);

        $en = $this->get(route('events.show', $event->showRoute()))->assertOk();
        $en->assertSee('hreflang="ar"', false);
        $en->assertSee('hreflang="en"', false);

        $arUrl = LocaleUrl::event($event, 'ar');
        $ar = $this->get(parse_url($arUrl, PHP_URL_PATH))->assertOk();
        $ar->assertSee('lang="ar"', false);
        $ar->assertSee('hreflang="en"', false);

        $this->assertSame(
            LocaleUrl::event($event, 'en'),
            LocaleUrl::alternatesForEvent($event)['x-default']
        );
    }

    public function test_draft_is_noindex_and_absent_from_sitemap(): void
    {
        Cache::forget('seo.sitemap.xml');

        $owner = User::factory()->active()->create();
        $draft = Event::factory()->create([
            'created_by' => $owner->id,
            'status' => EventStatus::Draft,
            'title' => 'Hidden Draft SEO',
            'slug' => 'hidden-draft-seo',
        ]);

        $this->actingAs($owner)
            ->get(route('events.show', $draft->showRoute()))
            ->assertOk()
            ->assertSee('noindex', false);

        $sitemap = $this->get(route('sitemap'))->assertOk();
        $sitemap->assertDontSee(event_show_url($draft), false);
        $sitemap->assertDontSee('/ar/events/'.$draft->id, false);
    }

    public function test_arabic_title_generates_unicode_slug_when_column_exists(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('events', 'slug_ar')) {
            $this->markTestSkipped('slug_ar migration not applied');
        }

        $owner = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'title' => 'تجمع ملاكي جيب شروكي',
            'status' => EventStatus::Published,
        ]);

        $this->assertNotNull($event->fresh()->slug_ar);
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', (string) $event->fresh()->slug_ar);

        $path = parse_url(LocaleUrl::event($event->fresh(), 'ar'), PHP_URL_PATH);
        $this->get($path)->assertOk();
    }

    public function test_legacy_event_url_still_works(): void
    {
        $owner = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
        ]);

        $this->get('/events/'.$event->id.'/'.$event->slug)->assertOk();
    }
}
