<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceholderCancellationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_without_cancelling(): void
    {
        $example = User::factory()->active()->create(['email' => 'owner@example.com']);
        $org = User::factory()->active()->create(['email' => 'owner@example.org']);
        $gmail = User::factory()->active()->create(['email' => 'player@gmail.com']);
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $this->insertEvent(501, $example->id, $category->id, $city->id, 'published', 'Title');
        $this->insertEvent(502, $example->id, $category->id, $city->id, 'published', 'yyyy');
        $this->insertEvent(602, $org->id, $category->id, $city->id, 'published', 'Modi');
        $this->insertEvent(503, $gmail->id, $category->id, $city->id, 'completed', 'Zamalek football match');
        $this->member(501, $example->id, 'TCK501');
        $this->member(502, $example->id, 'TCK502');
        $this->member(602, $org->id, 'TCK602');

        $this->artisan('seo:validate-placeholder-cancellations')->assertSuccessful();

        $this->assertSame('published', DB::table('events')->where('id', 501)->value('status'));
        $this->assertSame('published', DB::table('events')->where('id', 502)->value('status'));
        $this->assertSame('published', DB::table('events')->where('id', 602)->value('status'));
        $this->assertSame('completed', DB::table('events')->where('id', 503)->value('status'));
    }

    public function test_validation_fails_when_a_real_user_is_involved(): void
    {
        $example = User::factory()->active()->create(['email' => 'owner@example.com']);
        $gmail = User::factory()->active()->create(['email' => 'player@gmail.com']);
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $this->insertEvent(501, $example->id, $category->id, $city->id, 'published', 'Title');
        $this->insertEvent(502, $example->id, $category->id, $city->id, 'published', 'yyyy');
        $this->insertEvent(602, $example->id, $category->id, $city->id, 'published', 'Modi');
        $this->insertEvent(503, $gmail->id, $category->id, $city->id, 'completed', 'Zamalek football match');
        $this->member(501, $gmail->id, 'TCKREAL');

        $this->artisan('seo:validate-placeholder-cancellations')->assertFailed();
        $this->assertSame('published', DB::table('events')->where('id', 501)->value('status'));
        $this->assertSame('completed', DB::table('events')->where('id', 503)->value('status'));
    }

    private function insertEvent(int $id, int $userId, int $categoryId, int $cityId, string $status, string $title): void
    {
        $now = now()->toDateTimeString();
        DB::table('events')->insert([
            'id' => $id,
            'created_by' => $userId,
            'category_id' => $categoryId,
            'city_id' => $cityId,
            'title' => $title,
            'slug' => 'event-'.$id,
            'description' => 'Description',
            'starts_at' => $now,
            'max_participants' => 8,
            'status' => $status,
            'search_indexable' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function member(int $eventId, int $userId, string $ticket): void
    {
        $now = now()->toDateTimeString();
        DB::table('event_members')->insert([
            'event_id' => $eventId,
            'user_id' => $userId,
            'role' => 'owner',
            'ticket_code' => $ticket,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
