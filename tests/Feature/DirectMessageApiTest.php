<?php

namespace Tests\Feature;

use App\Enums\FriendshipStatus;
use App\Events\DirectMessagePosted;
use App\Jobs\NotifyFriendsOfPublishedEvent;
use App\Models\Category;
use App\Models\City;
use App\Models\DirectConversation;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\DirectMessagePushNotification;
use App\Notifications\FriendEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DirectMessageApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeFriends(User $a, User $b): void
    {
        Friendship::query()->create([
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
            'status' => FriendshipStatus::Accepted,
        ]);
    }

    public function test_friends_can_dm_and_list(): void
    {
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $this->makeFriends($a, $b);

        Sanctum::actingAs($a);
        $open = $this->postJson("/api/dms/with/{$b->id}")->assertCreated();
        $conversationId = $open->json('data.id');

        $this->postJson("/api/dms/{$conversationId}/messages", ['body' => 'Hi <b>friend</b>'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Hi friend');

        Sanctum::actingAs($b);
        $this->getJson("/api/dms/{$conversationId}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Hi friend');

        $this->getJson('/api/dms')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Notification::assertSentTo($b, DirectMessagePushNotification::class);
    }

    public function test_idor_on_conversation_forbidden(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $c = User::factory()->active()->create();
        $this->makeFriends($a, $b);

        [$low, $high] = DirectConversation::orderedPair($a->id, $b->id);
        $conversation = DirectConversation::query()->create([
            'user_low_id' => $low,
            'user_high_id' => $high,
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($c);
        $this->getJson("/api/dms/{$conversation->id}/messages")->assertForbidden();
        $this->postJson("/api/dms/{$conversation->id}/messages", ['body' => 'Nope'])->assertStatus(422);
    }

    public function test_dm_without_friendship_rejected(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        Sanctum::actingAs($a);
        $this->postJson("/api/dms/with/{$b->id}")->assertStatus(422);
    }

    public function test_publish_queues_friend_event_notify(): void
    {
        Queue::fake();

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create(['notify_friend_events' => true]);
        $this->makeFriends($a, $b);

        $event = Event::factory()->create([
            'created_by' => $a->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => \App\Enums\EventStatus::Draft,
        ]);

        Sanctum::actingAs($a);
        $this->postJson("/api/events/{$event->id}/publish")->assertOk();

        Queue::assertPushed(NotifyFriendsOfPublishedEvent::class);
    }

    public function test_notify_pref_update_and_me_payload(): void
    {
        $user = User::factory()->active()->create(['notify_friend_events' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('notify_friend_events', true);

        $this->putJson('/api/settings/notifications', ['notify_friend_events' => false])
            ->assertOk()
            ->assertJsonPath('data.notify_friend_events', false);

        $this->assertFalse((bool) $user->fresh()->notify_friend_events);
    }

    public function test_friend_event_job_respects_pref(): void
    {
        Notification::fake();

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create(['notify_friend_events' => false]);
        $c = User::factory()->active()->create(['notify_friend_events' => true]);
        $this->makeFriends($a, $b);
        $this->makeFriends($a, $c);

        $event = Event::factory()->published()->create([
            'created_by' => $a->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
        ]);

        (new NotifyFriendsOfPublishedEvent($event->id, $a->id))->handle(
            app(\App\Services\Friends\FriendshipService::class),
            app(\App\Services\Safety\UserBlockService::class),
        );

        Notification::assertNotSentTo($b, FriendEventNotification::class);
        Notification::assertSentTo($c, FriendEventNotification::class);
    }
}
