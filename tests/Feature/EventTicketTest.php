<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use App\Services\Events\BuildEventTicketService;
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

        $this->actingAs($member)
            ->get(route('events.ticket', $event))
            ->assertOk()
            ->assertSee('Sunset Meetup', false)
            ->assertSee(__('events.ticket_word'), false)
            ->assertSee('event-ticket', false);
    }

    public function test_non_member_cannot_view_ticket(): void
    {
        $member = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        $event = $this->publishedEventWithMember($member);

        $this->actingAs($outsider)
            ->get(route('events.ticket', $event))
            ->assertForbidden();

        Sanctum::actingAs($outsider);
        $this->getJson("/api/events/{$event->id}/ticket")
            ->assertForbidden();
    }

    public function test_api_returns_ticket_payload_for_member(): void
    {
        $member = User::factory()->active()->create(['name' => 'Ticket Holder']);
        $event = $this->publishedEventWithMember($member);
        $code = app(BuildEventTicketService::class)->codeFor($event->id, $member->id);

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
                    'member' => ['id', 'name'],
                    'event' => ['id', 'title', 'status'],
                ],
            ]);
    }
}
