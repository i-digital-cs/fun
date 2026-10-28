<?php

namespace Tests\Feature;

use App\Mail\AuthCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_email_verify_and_resend(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->active()->create([
            'email' => 'verify-api@example.com',
            'password' => 'Password1!',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify/resend')
            ->assertOk()
            ->assertJsonPath('message', __('auth.code_sent'));

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->postJson('/api/email/verify', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('email_verified', true);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_api_forgot_and_reset_password(): void
    {
        Mail::fake();

        $user = User::factory()->active()->create([
            'email' => 'reset-api@example.com',
            'password' => 'Password1!',
        ]);

        $this->postJson('/api/forgot-password', ['email' => 'reset-api@example.com'])
            ->assertOk();

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $verify = $this->postJson('/api/forgot-password/verify', [
            'email' => 'reset-api@example.com',
            'code' => $code,
        ])->assertOk();

        $resetToken = $verify->json('reset_token');
        $this->assertNotEmpty($resetToken);

        $reset = $this->postJson('/api/reset-password', [
            'email' => 'reset-api@example.com',
            'reset_token' => $resetToken,
            'password' => 'Password2!',
            'password_confirmation' => 'Password2!',
        ])->assertOk();

        $reset->assertJsonStructure(['token', 'user']);
        $this->assertTrue(Hash::check('Password2!', $user->fresh()->password));
    }

    public function test_api_forgot_password_sends_code_for_social_only_user(): void
    {
        Mail::fake();

        $user = User::factory()->active()->create([
            'email' => 'google-only@example.com',
            'password' => null,
            'google_id' => 'google-subject-123',
        ]);

        $this->assertNull($user->password);

        $this->postJson('/api/forgot-password', ['email' => 'google-only@example.com'])
            ->assertOk();

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code, $user) {
            $code = $mail->code;

            return $mail->user->is($user) && $mail->purpose === 'password_reset';
        });

        $verify = $this->postJson('/api/forgot-password/verify', [
            'email' => 'google-only@example.com',
            'code' => $code,
        ])->assertOk();

        $this->postJson('/api/reset-password', [
            'email' => 'google-only@example.com',
            'reset_token' => $verify->json('reset_token'),
            'password' => 'Password2!',
            'password_confirmation' => 'Password2!',
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $this->assertTrue(Hash::check('Password2!', $user->fresh()->password));
    }

    public function test_api_profile_password_change(): void
    {
        $user = User::factory()->active()->create([
            'password' => 'Password1!',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'sex' => $user->sex?->value ?? 'male',
            'locale' => 'en',
            'current_password' => 'Password1!',
            'password' => 'Password9!',
            'password_confirmation' => 'Password9!',
        ])
            ->assertOk()
            ->assertJsonPath('email_changed', false);

        $this->assertTrue(Hash::check('Password9!', $user->fresh()->password));
    }
}
