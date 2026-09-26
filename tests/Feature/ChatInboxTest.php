<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Events\EventMessagePosted;
use App\Events\UserChatMessageReceived;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatInboxTest extends TestCase
{
    use RefreshDatabase;

    private function membershipEvent(User $owner, User $member): Event
    {
        $event = Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'title' => 'Inbox Event',
            'max_participants' => 5,
        ]);

        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        return $event;
    }

    public function test_chat_inbox_lists_memberships_with_unread(): void
    {
        EventFacade::fake([EventMessagePosted::class, UserChatMessageReceived::class]);

        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->membershipEvent($owner, $member);

        Sanctum::actingAs($owner);
        $this->postJson("/api/events/{$event->id}/messages", ['body' => 'Hello member'])->assertCreated();

        Sanctum::actingAs($member);
        $this->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('meta.unread_total', 1)
            ->assertJsonPath('data.0.event_id', $event->id)
            ->assertJsonPath('data.0.title', 'Inbox Event')
            ->assertJsonPath('data.0.last_message.body', 'Hello member')
            ->assertJsonStructure([
                'data' => [
                    [
                        'event_id',
                        'title',
                        'image_url',
                        'starts_at',
                        'last_message' => ['id', 'body', 'created_at'],
                    ],
                ],
            ]);

        $this->get(route('chats.index'))
            ->assertOk()
            ->assertSee('Inbox Event')
            ->assertSee('Hello member')
            ->assertSee(__('chat.inbox_recent'))
            ->assertSee(__('chat.inbox_unread'));
    }

    public function test_opening_messages_marks_chat_read(): void
    {
        EventFacade::fake([EventMessagePosted::class, UserChatMessageReceived::class]);

        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->membershipEvent($owner, $member);

        Sanctum::actingAs($owner);
        $this->postJson("/api/events/{$event->id}/messages", ['body' => 'Ping'])->assertCreated();

        Sanctum::actingAs($member);
        $this->getJson('/api/chats')->assertJsonPath('meta.unread_total', 1);

        $this->getJson("/api/events/{$event->id}/messages")->assertOk();

        $this->getJson('/api/chats')->assertJsonPath('meta.unread_total', 0);
    }

    public function test_posting_message_notifies_other_members(): void
    {
        EventFacade::fake([EventMessagePosted::class, UserChatMessageReceived::class]);

        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->membershipEvent($owner, $member);

        Sanctum::actingAs($owner);
        $this->postJson("/api/events/{$event->id}/messages", ['body' => 'Notify me'])->assertCreated();

        EventFacade::assertDispatched(UserChatMessageReceived::class, function (UserChatMessageReceived $eventPayload) use ($member) {
            return $eventPayload->recipientUserId === (int) $member->id;
        });
    }
}
