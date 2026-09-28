<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AccountDeletionRequestedNotification;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_schedule_and_cancel_account_deletion_via_api(): void
    {
        Notification::fake();

        $user = User::factory()->active()->create([
            'password' => 'password',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/account/delete', ['password' => 'password'])
            ->assertOk()
            ->assertJsonPath('purge_after_days', 30);

        $user->refresh();
        $this->assertNotNull($user->deletion_requested_at);

        Notification::assertSentTo($user, AccountDeletionRequestedNotification::class);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/account/delete/cancel')
            ->assertOk();

        $user->refresh();
        $this->assertNull($user->deletion_requested_at);
    }

    public function test_legal_pages_are_indexable(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee(__('terms.title'), false);
        $this->get(route('legal.support'))->assertOk()->assertSee(__('support.title'), false);
        $this->get(route('legal.account-deletion'))->assertOk()->assertSee(__('account_deletion.title'), false);
    }

    public function test_apple_auth_rejects_when_not_configured(): void
    {
        config(['services.apple.client_ids' => []]);

        $this->postJson('/api/auth/apple', ['id_token' => 'not-a-real-token'])
            ->assertStatus(422);
    }
}
