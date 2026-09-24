<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Mail\AuthCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_must_verify_email(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'male',
            'locale' => 'ar',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ahmed@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(AccountStatus::PendingDocuments, $user->status);
        $this->assertSame('ar', $user->locale);
        $this->assertSame('male', $user->sex?->value);
        $this->assertNull($user->email_verified_at);

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('ahmed@example.com');
        });

        $this->assertNotNull($code);

        $this->post('/email/verify', ['code' => $code])
            ->assertRedirect(route('identity.create'));

        $this->assertNotNull($user->fresh()->email_verified_at);
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

    public function test_platform_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'email' => 'admin-login@example.com',
            'password' => 'Password1!',
        ]);

        $this->post('/login', [
            'email' => 'admin-login@example.com',
            'password' => 'Password1!',
        ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_api_register_and_login_return_sanctum_token(): void
    {
        Mail::fake();

        $register = $this->postJson('/api/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'female',
        ]);

        $register->assertCreated()->assertJsonStructure(['token', 'user' => ['status', 'sex']]);
        $this->assertSame('pending_documents', $register->json('user.status'));
        $this->assertSame('female', $register->json('user.sex'));
        $this->assertFalse($register->json('email_verified'));

        $login = $this->postJson('/api/login', [
            'email' => 'sara@example.com',
            'password' => 'Password1!',
        ]);

        $login->assertOk()->assertJsonStructure(['token']);
    }

    public function test_forgot_password_flow_with_email_code(): void
    {
        Mail::fake();

        $user = User::factory()->active()->create([
            'email' => 'resetme@example.com',
            'password' => 'Password1!',
        ]);

        $this->post('/forgot-password', ['email' => 'resetme@example.com'])
            ->assertRedirect(route('password.verify'));

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->post('/forgot-password/verify', ['code' => $code])
            ->assertRedirect(route('password.reset'));

        $this->post('/reset-password', [
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword1!', $user->fresh()->password));
    }
}
