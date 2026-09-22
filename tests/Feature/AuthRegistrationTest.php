<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_lands_as_pending_documents(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'locale' => 'ar',
        ]);

        $response->assertRedirect(route('identity.create'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ahmed@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(AccountStatus::PendingDocuments, $user->status);
        $this->assertSame('ar', $user->locale);
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'active@example.com',
            'password' => 'Password1!',
        ]);

        $response = $this->post('/login', [
            'email' => 'active@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_api_register_and_login_return_sanctum_token(): void
    {
        $register = $this->postJson('/api/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $register->assertCreated()->assertJsonStructure(['token', 'user' => ['status']]);
        $this->assertSame('pending_documents', $register->json('user.status'));

        $login = $this->postJson('/api/login', [
            'email' => 'sara@example.com',
            'password' => 'Password1!',
        ]);

        $login->assertOk()->assertJsonStructure(['token']);
    }
}
