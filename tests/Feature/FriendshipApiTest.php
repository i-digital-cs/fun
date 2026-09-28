<?php

namespace Tests\Feature;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FriendshipApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_accept_and_list_friends(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        Sanctum::actingAs($a);
        $this->postJson('/api/friends', ['public_id' => $b->public_id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        Sanctum::actingAs($b);
        $this->postJson("/api/friends/{$a->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->getJson('/api/friends')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $a->public_id);
    }

    public function test_authz_matrix_rejects_invalid_transitions(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();
        $c = User::factory()->active()->create();

        Sanctum::actingAs($a);

        $this->postJson('/api/friends', ['public_id' => $a->public_id])
            ->assertStatus(422);

        $this->postJson('/api/friends', ['public_id' => $b->public_id])->assertCreated();
        $this->postJson('/api/friends', ['public_id' => $b->public_id])->assertStatus(422);

        // Accept own outgoing
        $this->postJson("/api/friends/{$b->id}/accept")->assertStatus(422);

        // Cancel outgoing
        $this->postJson("/api/friends/{$b->id}/cancel")->assertOk();

        Friendship::query()->create([
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
            'status' => FriendshipStatus::Accepted,
        ]);

        $this->postJson('/api/friends', ['public_id' => $b->public_id])->assertStatus(422);

        // DM without friendship (c)
        $this->postJson("/api/dms/with/{$c->id}")->assertStatus(422);

        // Unfriend
        $this->deleteJson("/api/friends/{$b->id}")->assertOk();
    }

    public function test_blocked_users_cannot_friend(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        UserBlock::query()->create([
            'blocker_id' => $a->id,
            'blocked_id' => $b->id,
        ]);

        Sanctum::actingAs($a);
        $this->postJson('/api/friends', ['public_id' => $b->public_id])->assertStatus(422);
    }

    public function test_reverse_pending_does_not_duplicate(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        Sanctum::actingAs($a);
        $this->postJson('/api/friends', ['public_id' => $b->public_id])->assertCreated();

        Sanctum::actingAs($b);
        $this->postJson('/api/friends', ['public_id' => $a->public_id])->assertStatus(422);

        $this->assertEquals(1, Friendship::query()->count());

        $this->postJson("/api/friends/{$a->id}/accept")->assertOk();
    }

    public function test_declined_can_re_request(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        Sanctum::actingAs($a);
        $this->postJson('/api/friends', ['public_id' => $b->public_id])->assertCreated();

        Sanctum::actingAs($b);
        $this->postJson("/api/friends/{$a->id}/decline")->assertOk();

        Sanctum::actingAs($a);
        $this->postJson('/api/friends', ['public_id' => $b->public_id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertEquals(1, Friendship::query()->count());
    }

    public function test_search_by_public_id(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        Sanctum::actingAs($a);
        $this->getJson('/api/users/search?q='.$b->public_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $b->public_id);
    }

    public function test_profile_includes_friendship_state(): void
    {
        $a = User::factory()->active()->create();
        $b = User::factory()->active()->create();

        Sanctum::actingAs($a);
        $this->getJson('/api/users/'.$b->public_id)
            ->assertOk()
            ->assertJsonPath('data.friendship.can_request', true)
            ->assertJsonPath('data.can_message', false);
    }
}
