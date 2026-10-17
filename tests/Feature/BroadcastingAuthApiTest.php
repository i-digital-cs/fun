<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BroadcastingAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Channel callbacks bind to whichever broadcaster exists at boot.
        // The suite default is the null driver, which accepts every channel
        // with an empty 200. Boot this class on a local signer instead.
        $this->pinBroadcastEnv([
            'BROADCAST_CONNECTION' => 'pusher',
            'PUSHER_APP_ID' => 'test-app',
            'PUSHER_APP_KEY' => 'test-key',
            'PUSHER_APP_SECRET' => 'test-secret',
            'PUSHER_APP_CLUSTER' => 'mt1',
        ]);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->pinBroadcastEnv([
            'BROADCAST_CONNECTION' => 'null',
        ]);
        foreach (['PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER'] as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        parent::tearDown();
    }

    public function test_guest_cannot_authorize_broadcast_channel(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-App.Models.User.1',
        ])->assertUnauthorized();
    }

    public function test_user_can_authorize_own_private_channel(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-App.Models.User.'.$user->id,
        ])->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_user_cannot_authorize_another_users_channel(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-App.Models.User.'.$other->id,
        ])->assertForbidden();
    }

    /**
     * @param  array<string, string>  $values
     */
    private function pinBroadcastEnv(array $values): void
    {
        foreach ($values as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
