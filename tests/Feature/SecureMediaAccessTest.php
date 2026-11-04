<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Events\DirectMessagePosted;
use App\Models\Category;
use App\Models\City;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
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

class SecureMediaAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ChatV2Capabilities::reset();
        EventFacade::fake([DirectMessagePosted::class, \App\Events\UserDirectMessageReceived::class]);
        Notification::fake();
        Storage::fake(config('media.private_disk'));
    }

    private function friends(User $a, User $b): void
    {
        Friendship::query()->create([
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
            'status' => FriendshipStatus::Accepted,
        ]);
    }

    private function openDm(User $a, User $b): int
    {
        $this->friends($a, $b);
        Sanctum::actingAs($a);

        return (int) $this->postJson("/api/dms/with/{$b->id}")->assertCreated()->json('data.id');
    }

    private function postDmImage(User $a, int $conversationId): array
    {
        Sanctum::actingAs($a);
        $file = UploadedFile::fake()->image('photo.jpg', 200, 200);
        $created = $this->post("/api/dms/{$conversationId}/messages", [
            'body' => '',
            'media_kind' => 'image',
            'media' => $file,
            'client_uuid' => 'img'.substr(md5((string) microtime(true)), 0, 20),
        ], ['Accept' => 'application/json'])->assertCreated();

        return [
            'id' => (int) $created->json('data.id'),
            'media_url' => (string) $created->json('data.media_url'),
            'media_path' => (string) $created->json('data.media_path'),
        ];
    }

    public function test_authorized_participant_can_view_dm_image(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);
        $msg = $this->postDmImage($a, $conversationId);

        Sanctum::actingAs($b);
        $response = $this->get($msg['media_url'])->assertOk();
        $cache = strtolower((string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $cache);
        $this->assertStringContainsString('no-store', $cache);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith('chat/direct/', $msg['media_path']);
        $this->assertStringContainsString('/api/dms/media/', $msg['media_url']);
        $this->assertStringNotContainsString('r2.cloudflarestorage.com', $msg['media_url']);
    }

    public function test_copied_private_url_denied_to_outsider(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $c = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);
        $msg = $this->postDmImage($a, $conversationId);

        Sanctum::actingAs($c);
        $this->get($msg['media_url'])->assertForbidden();
    }

    public function test_logged_out_private_media_denied(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);
        $msg = $this->postDmImage($a, $conversationId);

        $this->app['auth']->forgetGuards();
        $this->getJson($msg['media_url'])->assertUnauthorized();
    }

    public function test_web_session_gateway_denies_outsider_and_allows_participant(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $c = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);
        $msg = $this->postDmImage($a, $conversationId);

        $this->actingAs($b)->get(route('dms.media', $msg['id']))->assertOk();
        $this->actingAs($c)->get(route('dms.media', $msg['id']))->assertForbidden();
    }

    public function test_event_chat_media_requires_membership(): void
    {
        Storage::fake(config('media.private_disk'));
        $host = User::factory()->active()->create();
        $guest = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();

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

        Sanctum::actingAs($host);
        $file = UploadedFile::fake()->image('cover.jpg', 120, 120);
        $created = $this->post("/api/events/{$event->id}/messages", [
            'media_kind' => 'image',
            'media' => $file,
            'client_uuid' => 'evimg'.substr(md5('x'), 0, 16),
        ], ['Accept' => 'application/json'])->assertCreated();

        $messageId = (int) $created->json('data.id');
        $url = (string) $created->json('data.media_url');
        $this->assertStringStartsWith('chat/events/', (string) $created->json('data.media_path'));

        Sanctum::actingAs($guest);
        $this->get($url)->assertOk();

        Sanctum::actingAs($outsider);
        $this->get($url)->assertForbidden();

        // Revoked membership: former participant must lose media access on subsequent requests.
        EventMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $guest->id)
            ->delete();

        Sanctum::actingAs($guest);
        $this->get($url)->assertForbidden();
    }

    public function test_rejects_svg_and_html_as_chat_image(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);

        $svg = UploadedFile::fake()->create('evil.svg', 20, 'image/svg+xml');
        $this->post("/api/dms/{$conversationId}/messages", [
            'media_kind' => 'image',
            'media' => $svg,
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $html = UploadedFile::fake()->create('x.html', 20, 'text/html');
        $this->post("/api/dms/{$conversationId}/messages", [
            'media_kind' => 'file',
            'media' => $html,
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_rejects_oversized_chat_image(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);

        // Laravel fake size is in KB; category max is 8MB → 9000 KB exceeds.
        $huge = UploadedFile::fake()->create('huge.jpg', 9000, 'image/jpeg');
        $this->post("/api/dms/{$conversationId}/messages", [
            'media_kind' => 'image',
            'media' => $huge,
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_rejects_path_traversal_filename(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);

        $file = UploadedFile::fake()->image('../../evil.jpg', 40, 40);
        $this->post("/api/dms/{$conversationId}/messages", [
            'media_kind' => 'image',
            'media' => $file,
        ], ['Accept' => 'application/json'])->assertCreated();

        $path = (string) DirectMessage::query()->latest('id')->value('media_path');
        $this->assertStringNotContainsString('..', $path);
        $this->assertStringStartsWith('chat/', $path);
    }

    public function test_missing_object_returns_404(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $this->friends($a, $b);
        [$low, $high] = DirectConversation::orderedPair($a->id, $b->id);
        $conversation = DirectConversation::query()->create([
            'user_low_id' => $low,
            'user_high_id' => $high,
            'last_message_at' => now(),
        ]);
        $message = DirectMessage::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $a->id,
            'body' => '',
            'message_type' => 'image',
            'media_path' => 'chat/direct/'.$conversation->id.'/missing-'.uniqid().'.jpg',
            'media_mime' => 'image/jpeg',
        ]);

        Sanctum::actingAs($a);
        $this->get('/api/dms/media/'.$message->id)->assertNotFound();
    }

    public function test_deleted_media_row_not_streamable_after_path_cleared(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $conversationId = $this->openDm($a, $b);
        $msg = $this->postDmImage($a, $conversationId);

        DirectMessage::query()->whereKey($msg['id'])->update(['media_path' => null]);

        Sanctum::actingAs($a);
        $this->get($msg['media_url'])->assertNotFound();
    }

    public function test_public_event_cover_url_remains_directly_fetchable(): void
    {
        Storage::fake(config('media.disk'));
        $disk = config('media.disk');
        $path = 'events/1/cover-test.jpg';
        Storage::disk($disk)->put($path, 'fake-jpeg-bytes');

        $event = Event::factory()->published()->create([
            'created_by' => User::factory()->active()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'image_path' => $path,
            'image_disk' => $disk,
        ]);

        $url = $event->imageUrl();
        $this->assertNotEmpty($url);
        $this->assertStringNotContainsString('/api/dms/media/', $url);
        $this->assertStringNotContainsString('/identity/', $url);
        // Public covers intentionally use the public disk URL (not the private gateway).
        $this->assertTrue(
            str_contains($url, '/storage/') || str_contains($url, $path) || str_starts_with($url, 'http'),
            'Expected a public disk URL for event covers'
        );
    }
}
