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

class EventTicketTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEventWithMember(User $member, EventMemberRole $role = EventMemberRole::Member): Event
    {
        $owner = User::factory()->active()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'title' => 'Sunset Meetup',
            'venue' => 'Riverside Park',
        ]);

        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'role' => $role,
        ]);

        return $event;
    }

    public function test_member_can_view_web_ticket(): void
    {
        $member = User::factory()->active()->create();
        $event = $this->publishedEventWithMember($member);
        $code = EventMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $member->id)
            ->value('ticket_code');

        $this->actingAs($member)
            ->get(event_route('events.ticket', $event))
            ->assertOk()
            ->assertSee('Sunset Meetup', false)
            ->assertSee(__('events.ticket_word'), false)
            ->assertSee('event-ticket-stage', false)
            ->assertSee('event-ticket', false)
            ->assertSee($code, false);
    }

    public function test_non_member_cannot_view_ticket(): void
    {
        $member = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        $event = $this->publishedEventWithMember($member);

        $this->actingAs($outsider)
            ->get(event_route('events.ticket', $event))
            ->assertForbidden();

        Sanctum::actingAs($outsider);
        $this->getJson("/api/events/{$event->id}/ticket")
            ->assertForbidden();
    }

    public function test_api_returns_ticket_payload_for_member(): void
    {
        $member = User::factory()->active()->create(['name' => 'Ticket Holder']);
        $event = $this->publishedEventWithMember($member);
        $code = EventMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $member->id)
            ->value('ticket_code');

        Sanctum::actingAs($member);

        $this->getJson("/api/events/{$event->id}/ticket")
            ->assertOk()
            ->assertJsonPath('data.code', $code)
            ->assertJsonPath('data.role', 'member')
            ->assertJsonPath('data.title', 'Sunset Meetup')
            ->assertJsonPath('data.event.id', $event->id)
            ->assertJsonPath('data.member.name', 'Ticket Holder')
            ->assertJsonStructure([
                'data' => [
                    'code',
                    'role',
                    'pass_label',
                    'title',
                    'date_label',
                    'date_iso',
                    'venue_line',
                    'barcode_shadow',
                    'member' => ['id', 'name'],
                    'event' => ['id', 'title', 'status'],
                ],
            ]);
    }

    public function test_each_membership_gets_a_unique_ticket_code(): void
    {
        $owner = User::factory()->active()->create();
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
        ]);

        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $a->id,
            'role' => EventMemberRole::Member,
        ]);
        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $b->id,
            'role' => EventMemberRole::Member,
        ]);

        $codes = EventMember::query()
            ->where('event_id', $event->id)
            ->pluck('ticket_code');

        $this->assertCount(3, $codes); // owner from factory + 2 members
        $this->assertSame($codes->count(), $codes->unique()->count());
        $this->assertTrue($codes->every(fn ($code) => is_string($code) && strlen($code) === 10));

        Sanctum::actingAs($a);
        $codeA = $this->getJson("/api/events/{$event->id}/ticket")->json('data.code');
        Sanctum::actingAs($b);
        $codeB = $this->getJson("/api/events/{$event->id}/ticket")->json('data.code');

        $this->assertNotSame($codeA, $codeB);
        $this->assertNotSame(
            $this->getJson("/api/events/{$event->id}/ticket")->json('data.barcode_shadow'),
            null
        );
    }
}
