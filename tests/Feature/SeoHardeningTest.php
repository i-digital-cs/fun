<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_clears_sitemap_cache_so_new_event_appears(): void
    {
        Cache::forget('seo.sitemap.xml');

        $owner = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $first = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'First Sitemap Event',
            'status' => EventStatus::Published,
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(event_show_url($first->fresh()), false);

        $draft = Event::factory()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Freshly Published Meetup',
            'description' => 'Has a description',
            'status' => EventStatus::Draft,
        ]);

        $this->get(route('sitemap'))->assertOk();
        $this->assertTrue(Cache::has('seo.sitemap.xml'));

        $this->actingAs($owner)
            ->post(event_route('events.publish', $draft))
            ->assertRedirect();

        $this->assertFalse(Cache::has('seo.sitemap.xml'));

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(event_show_url($draft->fresh()), false);
    }

    public function test_web_publish_without_description_shows_soft_warning(): void
    {
        $owner = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $event = Event::factory()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'No Description Yet',
            'description' => null,
            'status' => EventStatus::Draft,
        ]);

        $this->actingAs($owner)
            ->from(event_show_url($event))
            ->post(event_route('events.publish', $event))
            ->assertRedirect()
            ->assertSessionHas('status', __('events.published_missing_description'));
    }

    public function test_api_publish_without_description_sets_seo_warning_flag(): void
    {
        $owner = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $event = Event::factory()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Api Soft Warn Event',
            'description' => '',
            'status' => EventStatus::Draft,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/events/'.$event->id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('seo_warning', true)
            ->assertJsonPath('message', __('events.published_missing_description'));
    }

    public function test_seo_health_command_passes_offline_checks(): void
    {
        $exit = Artisan::call('seo:health', ['--skip-http' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('stock WebP', Artisan::output());
    }
}
