<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\DirectConversation;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedChatRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_dm_show_redirects_to_chats_workspace(): void
    {
        $user = User::factory()->active()->create();
        $friend = User::factory()->active()->create();
        Friendship::query()->create([
            'requester_id' => $user->id,
            'addressee_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
        ]);
        [$low, $high] = DirectConversation::orderedPair($user->id, $friend->id);
        $conversation = DirectConversation::query()->create([
            'user_low_id' => $low,
            'user_high_id' => $high,
            'last_message_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dms.show', $conversation))
            ->assertRedirect(chat_dm_url($conversation));
    }

    public function test_dms_open_lands_on_chats_workspace(): void
    {
        $user = User::factory()->active()->create();
        $friend = User::factory()->active()->create();
        Friendship::query()->create([
            'requester_id' => $user->id,
            'addressee_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
        ]);

        $response = $this->actingAs($user)
            ->get(route('dms.open', $friend));

        $conversation = DirectConversation::query()
            ->where('user_low_id', min($user->id, $friend->id))
            ->where('user_high_id', max($user->id, $friend->id))
            ->firstOrFail();

        $response->assertRedirect(chat_dm_url($conversation));
    }

    public function test_legacy_event_chat_page_redirects_to_chats_workspace(): void
    {
        $host = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $host->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'status' => EventStatus::Published,
        ]);

        $this->actingAs($host)
            ->get(event_route('events.chat', $event))
            ->assertRedirect(chat_event_url($event));
    }

    public function test_helpers_build_canonical_keys(): void
    {
        $this->assertStringContainsString('/chats?c=dm-12', chat_dm_url(12));
        $this->assertStringContainsString('/chats?c=event-9', chat_event_url(9));
    }

    public function test_unauthorized_event_chat_still_forbidden(): void
    {
        $host = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create();
        $event = Event::factory()->published()->create([
            'created_by' => $host->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'status' => EventStatus::Published,
        ]);

        $this->actingAs($outsider)
            ->get(event_route('events.chat', $event))
            ->assertForbidden();
    }

    public function test_unauthorized_dm_show_still_forbidden(): void
    {
        $user = User::factory()->active()->create();
        $friend = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        Friendship::query()->create([
            'requester_id' => $user->id,
            'addressee_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
        ]);
        [$low, $high] = DirectConversation::orderedPair($user->id, $friend->id);
        $conversation = DirectConversation::query()->create([
            'user_low_id' => $low,
            'user_high_id' => $high,
            'last_message_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->get(route('dms.show', $conversation))
            ->assertForbidden();
    }
}
