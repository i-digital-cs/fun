<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Events\DirectMessagePosted;
use App\Events\EventMessagePosted;
use App\Models\Category;
use App\Models\City;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Models\DirectMessageReaction;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\EventMessage;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Chat\ChatV2Capabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatV2ExtensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ChatV2Capabilities::reset();
    }

    private function makeFriends(User $a, User $b): void
    {
        Friendship::query()->create([
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
            'status' => FriendshipStatus::Accepted,
        ]);
    }

    private function openDm(User $a, User $b): int
    {
        $this->makeFriends($a, $b);
        Sanctum::actingAs($a);
        $open = $this->postJson("/api/dms/with/{$b->id}")->assertCreated();

        return (int) $open->json('data.id');
    }

    private function makeEventWithMembers(User $host, User $guest): Event
    {
        $event = Event::factory()->published()->create([
            'created_by' => $host->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
        ]);

        EventMember::query()->firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $host->id],
            ['role' => EventMemberRole::Owner]
        );
        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $guest->id,
            'role' => EventMemberRole::Member,
        ]);

        return $event;
    }

    public function test_reply_and_idempotency_on_dm(): void
    {
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);

        $first = $this->postJson("/api/dms/{$conversationId}/messages", [
            'body' => 'Hello',
            'client_uuid' => 'uuid-hello-1',
        ])->assertCreated();

        $messageId = (int) $first->json('data.id');

        $this->postJson("/api/dms/{$conversationId}/messages", [
            'body' => 'Hello',
            'client_uuid' => 'uuid-hello-1',
        ])->assertCreated()->assertJsonPath('data.id', $messageId);

        $reply = $this->postJson("/api/dms/{$conversationId}/messages", [
            'body' => 'Reply body',
            'reply_to_id' => $messageId,
            'client_uuid' => 'uuid-reply-1',
        ])->assertCreated();

        $this->assertSame($messageId, (int) $reply->json('data.reply_to_id'));
        $this->assertSame('Hello', $reply->json('data.reply_to.body'));
    }

    public function test_cross_conversation_reply_is_cleared(): void
    {
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $c = User::factory()->active()->create();
        $ab = $this->openDm($a, $b);
        $msg = $this->postJson("/api/dms/{$ab}/messages", ['body' => 'AB'])->assertCreated();
        $abMessageId = (int) $msg->json('data.id');

        $this->makeFriends($a, $c);
        Sanctum::actingAs($a);
        $ac = (int) $this->postJson("/api/dms/with/{$c->id}")->assertCreated()->json('data.id');

        $reply = $this->postJson("/api/dms/{$ac}/messages", [
            'body' => 'Should not link',
            'reply_to_id' => $abMessageId,
        ])->assertCreated();

        $this->assertNull($reply->json('data.reply_to_id'));
    }

    public function test_dm_reaction_toggle_unique(): void
    {
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);
        $messageId = (int) $this->postJson("/api/dms/{$conversationId}/messages", ['body' => 'React me'])
            ->assertCreated()
            ->json('data.id');

        Sanctum::actingAs($b);
        $this->postJson("/api/dms/{$conversationId}/messages/{$messageId}/react", ['emoji' => '👍'])
            ->assertOk()
            ->assertJsonPath('data.emoji_counts.👍', 1);

        $this->assertSame(1, DirectMessageReaction::query()->count());

        $this->postJson("/api/dms/{$conversationId}/messages/{$messageId}/react", ['emoji' => '👍'])
            ->assertOk();

        $this->assertSame(0, DirectMessageReaction::query()->count());
    }

    public function test_voice_upload_private_and_authorized(): void
    {
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();
        Storage::fake(config('media.private_disk'));

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $c = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);

        $file = UploadedFile::fake()->create('note.webm', 120, 'audio/webm');
        $created = $this->post("/api/dms/{$conversationId}/messages", [
            'body' => '',
            'media_kind' => 'voice',
            'duration_ms' => 1500,
            'media' => $file,
        ], ['Accept' => 'application/json'])->assertCreated();

        $messageId = (int) $created->json('data.id');
        $this->assertSame('voice', $created->json('data.message_type'));
        $this->assertNotEmpty($created->json('data.media_url'));

        Sanctum::actingAs($b);
        $this->get("/api/dms/media/{$messageId}")->assertOk();

        Sanctum::actingAs($c);
        $this->get("/api/dms/media/{$messageId}")->assertForbidden();
    }

    public function test_blocks_executable_attachment(): void
    {
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();
        Storage::fake(config('media.private_disk'));

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);

        $file = UploadedFile::fake()->create('evil.exe', 40, 'application/x-msdownload');
        $this->post("/api/dms/{$conversationId}/messages", [
            'media_kind' => 'file',
            'media' => $file,
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_event_chat_reply_and_like_preserved(): void
    {
        EventFacade::fake([EventMessagePosted::class, \App\Events\UserChatMessageReceived::class]);
        Notification::fake();

        $host = User::factory()->active()->create();
        $guest = User::factory()->active()->create();
        $event = $this->makeEventWithMembers($host, $guest);

        Sanctum::actingAs($host);
        $first = $this->postJson("/api/events/{$event->id}/messages", [
            'body' => 'Event hello',
            'client_uuid' => 'ev-1',
        ])->assertCreated();
        $messageId = (int) $first->json('data.id');

        Sanctum::actingAs($guest);
        $reply = $this->postJson("/api/events/{$event->id}/messages", [
            'body' => 'Event reply',
            'reply_to_id' => $messageId,
        ])->assertCreated();
        $this->assertSame($messageId, (int) $reply->json('data.reply_to_id'));

        $this->postJson("/api/events/{$event->id}/messages/{$messageId}/react", ['type' => 'like'])
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.my_reaction', 'like');
    }

    public function test_historical_counts_unaffected_by_extension_columns(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        [$low, $high] = DirectConversation::orderedPair($a->id, $b->id);
        $conversation = DirectConversation::query()->create([
            'user_low_id' => $low,
            'user_high_id' => $high,
            'last_message_at' => now(),
        ]);
        DirectMessage::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $a->id,
            'body' => 'legacy',
        ]);
        EventMessage::query()->create([
            'event_id' => $this->makeEventWithMembers($a, $b)->id,
            'user_id' => $a->id,
            'body' => 'legacy event',
            'is_system' => false,
        ]);

        $this->assertSame(1, DirectMessage::query()->count());
        $this->assertSame(1, EventMessage::query()->count());
        $this->assertTrue(ChatV2Capabilities::directMessageExtensions());
        $this->assertTrue(ChatV2Capabilities::eventMessageExtensions());
    }
}
