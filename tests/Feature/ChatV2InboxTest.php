<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\DirectConversation;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatV2InboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_chats_workspace_renders_two_panel_shell(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user)
            ->get(route('chats.index'))
            ->assertOk()
            ->assertSee('data-chat-v2', false)
            ->assertSee(__('chat.select_conversation'), false);
    }

    public function test_chats_workspace_opens_dm_panel(): void
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
            ->get(route('chats.index', ['c' => 'dm-'.$conversation->id]))
            ->assertOk()
            ->assertSee('id="dm-chat"', false)
            ->assertSee($friend->name);
    }

    public function test_chats_workspace_opens_event_panel_for_member(): void
    {
        $host = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create();

        $event = Event::factory()->published()->create([
            'created_by' => $host->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'status' => EventStatus::Published,
            'title' => 'Chat V2 Event Night',
        ]);

        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'role' => EventMemberRole::Member,
        ]);

        $this->actingAs($member)
            ->get(route('chats.index', ['c' => 'event-'.$event->id]))
            ->assertOk()
            ->assertSee('id="event-chat"', false)
            ->assertSee('Chat V2 Event Night');
    }
}
