<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\GoogleIdTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ApiGoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_id_token_login_issues_sanctum_token(): void
    {
        $this->mock(GoogleIdTokenVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')
                ->once()
                ->with('fake-id-token')
                ->andReturn([
                    'sub' => 'google-sub-123',
                    'email' => 'google.user@example.com',
                    'email_verified' => true,
                    'name' => 'Google User',
                    'picture' => 'https://example.com/avatar.jpg',
                ]);
        });

        $response = $this->postJson('/api/auth/google', [
            'id_token' => 'fake-id-token',
            'locale' => 'ar',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'email_verified']])
            ->assertJsonPath('user.email', 'google.user@example.com')
            ->assertJsonPath('email_verified', true)
            ->assertJsonPath('needs_identity_documents', true);

        $this->assertDatabaseHas('users', [
            'email' => 'google.user@example.com',
            'google_id' => 'google-sub-123',
            'locale' => 'ar',
        ]);

        $user = User::query()->where('email', 'google.user@example.com')->first();
        $this->assertNotNull($user?->email_verified_at);
    }

    public function test_google_login_links_existing_email_account(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
            'email_verified_at' => null,
        ]);

        $this->mock(GoogleIdTokenVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                'sub' => 'google-sub-999',
                'email' => 'existing@example.com',
                'email_verified' => true,
                'name' => 'Existing',
                'picture' => null,
            ]);
        });

        $this->postJson('/api/auth/google', ['id_token' => 'tok'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $user->refresh();
        $this->assertSame('google-sub-999', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
