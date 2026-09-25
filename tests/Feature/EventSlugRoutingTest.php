<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSlugRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_event_gets_a_slug_and_canonical_url(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Friday Night Football'])->fresh();

        $this->assertNotEmpty($event->slug);
        $this->assertStringContainsString('/events/'.$event->id.'/', event_show_url($event));
        $this->assertStringContainsString($event->slug, event_show_url($event));
    }

    public function test_bare_event_id_redirects_to_slug_url(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Slug Redirect Meetup'])->fresh();

        $this->get('/events/'.$event->id)
            ->assertStatus(301)
            ->assertRedirect(event_show_url($event));
    }

    public function test_wrong_slug_redirects_to_canonical(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Correct Slug Event'])->fresh();

        $this->get('/events/'.$event->id.'/wrong-slug')
            ->assertStatus(301)
            ->assertRedirect(event_show_url($event));
    }

    public function test_canonical_slug_url_renders_ok(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Canonical Render Event'])->fresh();

        $this->get(event_show_url($event))
            ->assertOk()
            ->assertSee('Canonical Render Event', false)
            ->assertSee('Breadcrumb', false);
    }

    public function test_reserved_action_paths_are_not_captured_as_slugs(): void
    {
        $host = User::factory()->active()->create();
        $event = $this->makePublishedEvent([
            'title' => 'Action Path Event',
            'created_by' => $host->id,
        ])->fresh();

        $this->get('/events/'.$event->id.'/edit')
            ->assertRedirect(event_route('events.edit', $event));

        $this->get(event_route('events.edit', $event))
            ->assertRedirect(route('login'));

        $this->actingAs($host)
            ->get(event_route('events.edit', $event))
            ->assertOk();

        $this->assertStringContainsString('/'.$event->slug.'/', event_route('events.chat', $event));
        $this->assertStringContainsString('/'.$event->slug.'/', event_route('events.ticket', $event));
    }

    public function test_discover_with_filters_is_noindex_with_clean_canonical(): void
    {
        $this->get(route('events.index', ['q' => 'padel']))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,follow"', false)
            ->assertSee('rel="canonical" href="'.route('events.index').'"', false);
    }

    public function test_api_event_includes_slug(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Api Slug Event'])->fresh();

        $this->getJson('/api/events/'.$event->id)
            ->assertOk()
            ->assertJsonPath('data.slug', $event->slug);
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
