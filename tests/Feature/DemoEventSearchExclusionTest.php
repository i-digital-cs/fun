<?php

namespace Tests\Feature;

use App\Http\Resources\EventResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemoEventSearchExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_indexable_event_stays_public_and_leaves_the_sitemap(): void
    {
        $event = Event::factory()->published()->create([
            'title' => 'Demo Crawl Target',
            'description' => 'Join us for a public demo that should stay bookable.',
        ]);
        $event->forceFill(['search_indexable' => false])->saveQuietly();
        $kept = Event::factory()->published()->create(['title' => 'Real Crawl Target']);

        Cache::forget('seo.sitemap.xml');

        $this->get(event_show_url($event))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,follow"', false)
            ->assertSee('"@type":"Event"', false)
            ->assertSee(event_show_url($event), false);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('Demo Crawl Target', false);

        $sitemap = $this->get(route('sitemap'));
        $sitemap->assertOk();
        $sitemap->assertDontSee(event_show_url($event), false);
        $sitemap->assertSee(event_show_url($kept), false);

        $payload = (new EventResource($event->fresh()))->resolve(request());
        $this->assertArrayNotHasKey('search_indexable', $payload);
        $this->assertSame('published', $payload['status']);
    }

    public function test_command_marks_only_the_confirmed_five_hundred_and_can_rerun(): void
    {
        [$creator, $category, $city] = $this->fixtures('seed@example.com');
        $this->insertDemoBlock($creator, $category, $city);

        $real = Event::factory()->published()->create([
            'title' => 'Real upcoming',
            'created_by' => User::factory()->active()->create(['email' => 'player@gmail.com'])->id,
        ]);

        $this->artisan('seo:exclude-demo-events')->assertSuccessful();
        $this->artisan('seo:exclude-demo-events')->assertSuccessful();

        $this->assertSame(0, DB::table('events')->whereBetween('id', [1, 500])->where('search_indexable', true)->count());
        $this->assertSame(500, DB::table('events')->whereBetween('id', [1, 500])->where('status', 'published')->count());
        $this->assertTrue((bool) $real->fresh()->search_indexable);
    }

    public function test_command_aborts_when_the_demo_set_is_not_exact(): void
    {
        [$creator, $category, $city] = $this->fixtures('seed@example.com');
        $this->insertDemoBlock($creator, $category, $city, 250);

        $this->artisan('seo:exclude-demo-events')->assertFailed();

        $this->assertSame(500, DB::table('events')->whereBetween('id', [1, 500])->where('search_indexable', true)->count());
    }

    public function test_command_aborts_when_a_creator_is_not_example_com(): void
    {
        [$creator, $category, $city] = $this->fixtures('seed@example.com');
        $other = User::factory()->active()->create(['email' => 'real@gmail.com']);
        $this->insertDemoBlock($creator, $category, $city);
        DB::table('events')->where('id', 12)->update(['created_by' => $other->id]);

        $this->artisan('seo:exclude-demo-events')->assertFailed();

        $this->assertSame(500, DB::table('events')->whereBetween('id', [1, 500])->where('search_indexable', true)->count());
    }

    public function test_command_aborts_when_a_payment_row_exists(): void
    {
        Schema::create('payments', function ($table) {
            $table->id();
            $table->foreignId('event_id');
            $table->integer('amount');
        });

        try {
            [$creator, $category, $city] = $this->fixtures('seed@example.com');
            $this->insertDemoBlock($creator, $category, $city);
            DB::table('payments')->insert(['event_id' => 4, 'amount' => 100]);

            $this->artisan('seo:exclude-demo-events')->assertFailed();

            $this->assertSame(500, DB::table('events')->whereBetween('id', [1, 500])->where('search_indexable', true)->count());
        } finally {
            Schema::dropIfExists('payments');
        }
    }

    /**
     * @return array{0: User, 1: Category, 2: City}
     */
    private function fixtures(string $email): array
    {
        return [
            User::factory()->active()->create(['email' => $email]),
            Category::factory()->create(),
            City::factory()->create(),
        ];
    }

    private function insertDemoBlock(User $creator, Category $category, City $city, ?int $brokenId = null): void
    {
        $now = now()->toDateTimeString();
        $rows = [];

        for ($id = 1; $id <= 500; $id++) {
            $rows[] = [
                'id' => $id,
                'created_by' => $creator->id,
                'category_id' => $category->id,
                'city_id' => $city->id,
                'title' => 'Demo '.$id,
                'slug' => 'demo-'.$id,
                'description' => $brokenId === $id ? 'Not a seed row' : 'Join us for a demo meetup.',
                'starts_at' => $now,
                'max_participants' => 8,
                'status' => 'published',
                'search_indexable' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('events')->insert($chunk);
        }
    }
}
