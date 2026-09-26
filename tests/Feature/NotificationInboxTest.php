<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Events\UserJoinAccepted;
use App\Events\UserJoinRequested;
use App\Models\Category;
use App\Models\City;
use App\Models\DeviceToken;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMember;
use App\Models\User;
use App\Notifications\ChatMessagePushNotification;
use App\Notifications\JoinRequestAcceptedNotification;
use App\Notifications\JoinRequestReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(User $owner): Event
    {
        return Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'max_participants' => 5,
        ]);
    }

    public function test_join_request_notifies_owner_via_notification(): void
    {
        Notification::fake();
        EventFacade::fake([UserJoinRequested::class]);

        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        Sanctum::actingAs($applicant);
        $this->postJson("/api/events/{$event->id}/join")->assertCreated();

        Notification::assertSentTo($owner, JoinRequestReceivedNotification::class);
        EventFacade::assertDispatched(UserJoinRequested::class);
    }

    public function test_join_request_appears_in_web_and_api_inbox(): void
    {
        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        Sanctum::actingAs($applicant);
        $this->postJson("/api/events/{$event->id}/join")->assertCreated();

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());

        Sanctum::actingAs($owner);
        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('data.0.type', 'join_request')
            ->assertJsonPath('data.0.event_id', $event->id);

        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('events.notify_join_request_title'));

        $id = $owner->notifications()->firstOrFail()->id;

        $this->postJson("/api/notifications/{$id}/read")->assertOk();
        $this->assertNotNull($owner->notifications()->whereKey($id)->first()?->read_at);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->postJson('/api/notifications/read-all')->assertOk();
    }

    public function test_accept_join_notifies_applicant_internally_and_broadcasts(): void
    {
        Notification::fake();
        EventFacade::fake([UserJoinAccepted::class]);

        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        $joinRequest = EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        Sanctum::actingAs($owner);
        $this->postJson("/api/events/{$event->id}/requests/{$joinRequest->id}/accept")
            ->assertOk();

        Notification::assertSentTo(
            $applicant,
            JoinRequestAcceptedNotification::class,
            function (JoinRequestAcceptedNotification $notification) use ($event) {
                $data = $notification->toArray($event->creator()->first() ?? $notification);

                return ($data['type'] ?? null) === 'join_accepted'
                    && (int) ($data['event_id'] ?? 0) === (int) $event->id;
            }
        );
        EventFacade::assertDispatched(UserJoinAccepted::class);
    }

    public function test_accept_join_appears_in_applicant_inbox(): void
    {
        EventFacade::fake([UserJoinAccepted::class]);

        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        $joinRequest = EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        Sanctum::actingAs($owner);
        $this->postJson("/api/events/{$event->id}/requests/{$joinRequest->id}/accept")
            ->assertOk();

        $this->assertSame(1, $applicant->fresh()->unreadNotifications()->count());

        Sanctum::actingAs($applicant);
        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'join_accepted')
            ->assertJsonPath('data.0.event_id', $event->id);
    }

    public function test_chat_message_sends_fcm_notification_not_inbox(): void
    {
        Notification::fake();
        EventFacade::fake([
            \App\Events\EventMessagePosted::class,
            \App\Events\UserChatMessageReceived::class,
        ]);

        $owner = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $event = $this->publishedEvent($owner);

        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        Sanctum::actingAs($owner);
        $this->postJson("/api/events/{$event->id}/messages", [
            'body' => 'Hello team',
        ])->assertCreated();

        Notification::assertSentTo($member, ChatMessagePushNotification::class);
        Notification::assertNotSentTo($owner, ChatMessagePushNotification::class);

        $this->assertSame(0, $member->fresh()->notifications()->count());
    }

    public function test_device_token_register_and_forget(): void
    {
        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/device-tokens', [
            'token' => 'fcm-test-token-abc',
            'platform' => 'android',
        ])->assertCreated();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-test-token-abc',
            'platform' => 'android',
        ]);

        $this->deleteJson('/api/device-tokens', [
            'token' => 'fcm-test-token-abc',
        ])->assertOk();

        $this->assertSame(0, DeviceToken::query()->where('user_id', $user->id)->count());
    }
}
