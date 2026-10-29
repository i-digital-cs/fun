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

        // Idempotent replay (lost HTTP / multi-tap Retry) returns 200, same row.
        $this->postJson("/api/dms/{$conversationId}/messages", [
            'body' => 'Hello',
            'client_uuid' => 'uuid-hello-1',
        ])->assertOk()->assertJsonPath('data.id', $messageId);

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

        $fixture = base_path('tests/fixtures/chat/tone5.webm');
        $this->assertFileExists($fixture);
        $file = new UploadedFile($fixture, 'tone5.webm', 'audio/webm', null, true);
        $created = $this->actingAs($a)->post("/api/dms/{$conversationId}/messages", [
            'body' => '',
            'media_kind' => 'voice',
            'duration_ms' => 5000,
            'media' => $file,
            'client_uuid' => 'testvoice'.substr(md5((string) microtime(true)), 0, 16),
        ], ['Accept' => 'application/json'])->assertCreated();

        $messageId = (int) $created->json('data.id');
        $this->assertSame('voice', $created->json('data.message_type'));
        $this->assertSame('audio/webm', $created->json('data.media_mime'));
        $this->assertSame(5000, (int) $created->json('data.media_duration_ms'));
        $this->assertGreaterThan(1000, (int) $created->json('data.media_bytes'));
        $this->assertNotEmpty($created->json('data.media_url'));

        Sanctum::actingAs($b);
        $ok = $this->get("/api/dms/media/{$messageId}");
        $ok->assertOk();
        $ok->assertHeader('Accept-Ranges', 'bytes');
        $ok->assertHeader('Content-Type', 'audio/webm');
        $this->assertGreaterThan(1000, strlen($ok->streamedContent()));

        $partial = $this->get("/api/dms/media/{$messageId}", ['Range' => 'bytes=0-1023']);
        $partial->assertStatus(206);
        $partial->assertHeader('Content-Range');

        // Flutter AAC / m4a path
        $m4aPath = base_path('tests/fixtures/chat/tone5.m4a');
        $this->assertFileExists($m4aPath);
        $m4a = new UploadedFile($m4aPath, 'tone5.m4a', 'audio/mp4', null, true);
        $m4aCreated = $this->actingAs($a)->post("/api/dms/{$conversationId}/messages", [
            'body' => '',
            'media_kind' => 'voice',
            'duration_ms' => 5000,
            'media' => $m4a,
            'client_uuid' => 'testm4a'.substr(md5((string) microtime(true)), 0, 16),
        ], ['Accept' => 'application/json'])->assertCreated();
        $this->assertSame('audio/mp4', $m4aCreated->json('data.media_mime'));
        Sanctum::actingAs($b);
        $this->get('/api/dms/media/'.(int) $m4aCreated->json('data.id'))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mp4');

        Sanctum::actingAs($c);
        $this->get("/api/dms/media/{$messageId}")->assertForbidden();
    }

    public function test_voice_retry_reuses_client_uuid_without_duplicating_message_or_media(): void
    {
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();
        $disk = config('media.private_disk');
        Storage::fake($disk);

        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);
        $clientUuid = 'retryuuid'.substr(md5('voice-v3-retry'), 0, 16);
        $fixture = base_path('tests/fixtures/chat/tone5.webm');

        // 1) First attempt fails before persistence (invalid empty body + no media).
        $this->actingAs($a)->post("/api/dms/{$conversationId}/messages", [
            'body' => '',
            'client_uuid' => $clientUuid,
        ], ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertSame(0, DirectMessage::query()->count());

        // 2) Successful persist (simulates first upload that the client may lose).
        $created = $this->actingAs($a)->post("/api/dms/{$conversationId}/messages", [
            'body' => '',
            'media_kind' => 'voice',
            'duration_ms' => 5000,
            'media' => new UploadedFile($fixture, 'tone5.webm', 'audio/webm', null, true),
            'client_uuid' => $clientUuid,
        ], ['Accept' => 'application/json'])->assertCreated();

        $messageId = (int) $created->json('data.id');
        $mediaPath = (string) $created->json('data.media_path');
        $this->assertNotSame('', $mediaPath);
        $this->assertTrue(Storage::disk($disk)->exists($mediaPath));
        $filesAfterFirst = Storage::disk($disk)->allFiles('chat');
        $this->assertCount(1, $filesAfterFirst);
        $this->assertSame(1, DirectMessage::query()->where('message_type', 'voice')->count());

        // 3) Lost HTTP response + multi-click Retry: same client_uuid, same message, no new file.
        for ($i = 0; $i < 3; $i++) {
            $replay = $this->actingAs($a)->post("/api/dms/{$conversationId}/messages", [
                'body' => '',
                'media_kind' => 'voice',
                'duration_ms' => 5000,
                'media' => new UploadedFile($fixture, 'tone5-retry.webm', 'audio/webm', null, true),
                'client_uuid' => $clientUuid,
            ], ['Accept' => 'application/json']);

            $replay->assertOk();
            $this->assertSame($messageId, (int) $replay->json('data.id'));
            $this->assertSame($mediaPath, (string) $replay->json('data.media_path'));
            $this->assertSame($clientUuid, (string) $replay->json('data.client_uuid'));
        }

        $this->assertSame(1, DirectMessage::query()->count());
        $this->assertSame(1, DirectMessage::query()->where('client_uuid', $clientUuid)->count());
        $this->assertSame([$mediaPath], Storage::disk($disk)->allFiles('chat'));

        // 4) Realtime-before-HTTP equivalent: finding by client_uuid returns the persisted row
        //    without requiring a second store (broadcast payload uses same id/uuid/media_url).
        $existing = app(\App\Services\Friends\DirectMessageService::class)->findExistingByClientUuid(
            $a,
            DirectConversation::query()->findOrFail($conversationId),
            $clientUuid
        );
        $this->assertNotNull($existing);
        $this->assertSame($messageId, (int) $existing->id);
        $this->assertSame($mediaPath, (string) $existing->media_path);
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
