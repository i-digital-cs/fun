<?php

namespace Tests\Feature;

use App\Enums\FriendshipStatus;
use App\Models\City;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeopleSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggestions_exclude_self_friends_blocked_and_omit_coordinates(): void
    {
        $city = City::factory()->create();

        $viewer = User::factory()->active()->create(['city_id' => $city->id]);
        $peer = User::factory()->active()->create(['city_id' => $city->id]);
        $friend = User::factory()->active()->create(['city_id' => $city->id]);
        $blocked = User::factory()->active()->create(['city_id' => $city->id]);

        Friendship::query()->create([
            'requester_id' => $viewer->id,
            'addressee_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
        ]);

        UserBlock::query()->create([
            'blocker_id' => $viewer->id,
            'blocked_id' => $blocked->id,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/people/suggestions');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('user.id');
        $this->assertTrue($ids->contains($peer->id));
        $this->assertFalse($ids->contains($viewer->id));
        $this->assertFalse($ids->contains($friend->id));
        $this->assertFalse($ids->contains($blocked->id));

        $payload = json_encode($response->json());
        $this->assertStringNotContainsString('latitude', $payload);
        $this->assertStringNotContainsString('longitude', $payload);
    }
}
