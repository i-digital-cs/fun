<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Events\EventMessagePosted;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventChatTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEventFor(User $owner): Event
    {
        return Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'max_participants' => 5,
        ]);
    }

    public function test_member_can_post_and_list_messages_via_api(): void
    {
        EventFacade::fake([EventMessagePosted::class]);

        $owner = User::factory()->active()->create();
        $event = $this->publishedEventFor($owner);

        Sanctum::actingAs($owner);

        $this->postJson("/api/events/{$event->id}/messages", [
            'body' => 'Hello <b>team</b> <script>x</script>',
        ])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Hello team x')
            ->assertJsonPath('data.user.id', $owner->id);

        EventFacade::assertDispatched(EventMessagePosted::class);

        $this->getJson("/api/events/{$event->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Hello team x');
    }

    public function test_non_member_cannot_access_chat(): void
    {
        $owner = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        $event = $this->publishedEventFor($owner);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/events/{$event->id}/messages")->assertForbidden();
        $this->postJson("/api/events/{$event->id}/messages", ['body' => 'Hi'])->assertForbidden();
        $this->get(route('events.chat', $event))->assertForbidden();
    }

    public function test_pending_user_cannot_use_chat(): void
    {
        $owner = User::factory()->active()->create();
        $event = $this->publishedEventFor($owner);
        $pending = User::factory()->create(); // pending_documents

        $event->members()->create([
            'user_id' => $pending->id,
            'role' => \App\Enums\EventMemberRole::Member,
        ]);

        Sanctum::actingAs($pending);

        $this->postJson("/api/events/{$event->id}/messages", ['body' => 'Hi'])->assertForbidden();
    }

    public function test_rejects_oversized_and_empty_after_sanitize(): void
    {
        EventFacade::fake([EventMessagePosted::class]);

        $owner = User::factory()->active()->create();
        $event = $this->publishedEventFor($owner);

        Sanctum::actingAs($owner);

        $this->postJson("/api/events/{$event->id}/messages", [
            'body' => str_repeat('a', 1001),
        ])->assertUnprocessable();

        $this->postJson("/api/events/{$event->id}/messages", [
            'body' => '<p>   </p>',
        ])->assertUnprocessable();
    }

    public function test_rejects_duplicate_message_within_window(): void
    {
        EventFacade::fake([EventMessagePosted::class]);

        $owner = User::factory()->active()->create();
        $event = $this->publishedEventFor($owner);

        Sanctum::actingAs($owner);

        $this->postJson("/api/events/{$event->id}/messages", ['body' => 'Same text'])
            ->assertCreated();

        $this->postJson("/api/events/{$event->id}/messages", ['body' => 'Same text'])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('chat.duplicate'));

        $this->assertSame(1, EventMessage::query()->count());
    }

    public function test_event_chat_channel_authorizer(): void
    {
        $owner = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        $pending = User::factory()->create();
        $event = $this->publishedEventFor($owner);

        $authorizer = new \App\Broadcasting\EventChatChannel;

        $this->assertTrue($authorizer->join($owner, $event->id));
        $this->assertFalse($authorizer->join($outsider, $event->id));
        $this->assertFalse($authorizer->join($pending, $event->id));
        $this->assertFalse($authorizer->join($owner, 999999));
    }

    public function test_web_chat_page_renders_for_member(): void
    {
        $owner = User::factory()->active()->create();
        $event = $this->publishedEventFor($owner);

        EventMessage::query()->create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'body' => 'Kickoff at eight',
            'is_system' => false,
        ]);

        $this->actingAs($owner)
            ->get(route('events.chat', $event))
            ->assertOk()
            ->assertSee(__('chat.title'))
            ->assertSee('Kickoff at eight')
            ->assertSee(__('chat.send'));
    }

    public function test_idor_cannot_post_to_another_event_by_swapping_id(): void
    {
        EventFacade::fake([EventMessagePosted::class]);

        $ownerA = User::factory()->active()->create();
        $ownerB = User::factory()->active()->create();
        $eventA = $this->publishedEventFor($ownerA);
        $eventB = $this->publishedEventFor($ownerB);

        Sanctum::actingAs($ownerA);

        $this->postJson("/api/events/{$eventB->id}/messages", ['body' => 'Nope'])
            ->assertForbidden();

        $this->assertDatabaseMissing('event_messages', [
            'event_id' => $eventB->id,
            'user_id' => $ownerA->id,
        ]);

        $this->postJson("/api/events/{$eventA->id}/messages", ['body' => 'Yes'])
            ->assertCreated();
    }
}
