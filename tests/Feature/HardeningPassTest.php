<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HardeningPassTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_api_does_not_expose_emails(): void
    {
        $owner = User::factory()->active()->create(['email' => 'owner-secret@example.com']);
        $member = User::factory()->active()->create(['email' => 'member-secret@example.com']);
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
            'max_participants' => 5,
        ]);

        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/events/{$event->id}/members")
            ->assertOk()
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonPath('data.0.name', $owner->name);

        $payload = $response->json();
        $encoded = json_encode($payload);

        $this->assertStringNotContainsString('owner-secret@example.com', $encoded);
        $this->assertStringNotContainsString('member-secret@example.com', $encoded);
        $this->assertArrayHasKey('meta', $payload);
    }

    public function test_privileged_user_fields_are_not_mass_assignable(): void
    {
        $user = User::factory()->active()->create();

        $user->fill([
            'status' => 'deactivated',
            'platform_role' => 'super_admin',
            'deactivation_reason' => 'hacked',
        ])->save();

        $user->refresh();

        $this->assertSame('active', $user->status->value);
        $this->assertNull($user->platform_role);
        $this->assertNull($user->deactivation_reason);
    }

    public function test_event_status_and_created_by_are_not_mass_assignable(): void
    {
        $owner = User::factory()->active()->create();
        $other = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $event = Event::factory()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Draft,
        ]);

        $event->fill([
            'status' => EventStatus::Published->value,
            'created_by' => $other->id,
            'title' => 'Safe title',
        ])->save();

        $event->refresh();

        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertSame($owner->id, $event->created_by);
        $this->assertSame('Safe title', $event->title);
    }

    public function test_is_full_uses_loaded_members_count_without_extra_query(): void
    {
        $owner = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'max_participants' => 1,
            'status' => EventStatus::Published,
        ]);

        $event = Event::query()->withCount('members')->findOrFail($event->id);

        $this->assertTrue($event->isFull());
        $this->assertSame(1, $event->acceptedMembersCount());
    }

    public function test_deactivated_user_cannot_update_location(): void
    {
        $city = City::factory()->create();
        $user = User::factory()->deactivated()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/settings/location', [
            'city_id' => $city->id,
            'distance_km' => 10,
        ])->assertForbidden();
    }
}
