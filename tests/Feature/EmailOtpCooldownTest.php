<?php

namespace Tests\Feature;

use App\Enums\EmailAuthPurpose;
use App\Mail\AuthCodeMail;
use App\Models\User;
use App\Services\Auth\EmailAuthCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailOtpCooldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_returns_cooldown_metadata_and_blocks_during_cooldown(): void
    {
        Mail::fake();
        config(['otp.resend_cooldown_seconds' => 60]);

        $user = User::factory()->unverified()->active()->create([
            'email' => 'otp-cooldown@example.com',
        ]);
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/email/verify/resend')
            ->assertOk()
            ->assertJsonPath('issued', true)
            ->assertJsonPath('retry_after_seconds', 60)
            ->assertJsonStructure(['resend_available_at', 'expires_in_minutes', 'email']);

        $this->assertNotEmpty($first->json('resend_available_at'));
        Mail::assertSent(AuthCodeMail::class, 1);

        $second = $this->postJson('/api/email/verify/resend')
            ->assertStatus(429)
            ->assertJsonStructure(['retry_after_seconds', 'resend_available_at', 'message']);

        $this->assertGreaterThan(0, (int) $second->json('retry_after_seconds'));
        $this->assertSame(
            (string) $second->json('retry_after_seconds'),
            $second->headers->get('Retry-After'),
        );
        Mail::assertSent(AuthCodeMail::class, 1);
    }

    public function test_concurrent_resend_only_issues_one_code(): void
    {
        Mail::fake();
        config(['otp.resend_cooldown_seconds' => 60]);

        $user = User::factory()->unverified()->active()->create([
            'email' => 'otp-race@example.com',
        ]);

        $service = app(EmailAuthCodeService::class);

        $results = [];
        $errors = 0;
        for ($i = 0; $i < 2; $i++) {
            try {
                $results[] = $service->issueAndSend($user, EmailAuthPurpose::EmailVerify);
            } catch (\App\Exceptions\OtpCooldownException) {
                $errors++;
            }
        }

        $this->assertCount(1, $results);
        $this->assertSame(1, $errors);
        Mail::assertSent(AuthCodeMail::class, 1);
        $this->assertDatabaseCount('email_auth_codes', 1);
    }

    public function test_verify_rejects_wrong_code_and_invalidates_after_max_attempts(): void
    {
        Mail::fake();
        config(['otp.max_verify_attempts' => 3]);

        $user = User::factory()->unverified()->active()->create([
            'email' => 'otp-attempts@example.com',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify/resend')->assertOk();

        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/email/verify', ['code' => '0000'])
                ->assertStatus(422)
                ->assertJsonPath('message', __('auth.invalid_code'));
        }

        $this->postJson('/api/email/verify', ['code' => '0000'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('auth.too_many_verify_attempts'));

        $this->assertDatabaseHas('email_auth_codes', [
            'user_id' => $user->id,
        ]);
        $this->assertNotNull(
            \App\Models\EmailAuthCode::query()->where('user_id', $user->id)->latest('id')->first()?->consumed_at
        );
    }

    public function test_verify_accepts_valid_code_and_responses_never_leak_plaintext(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->active()->create([
            'email' => 'otp-ok@example.com',
        ]);
        Sanctum::actingAs($user);

        $resend = $this->postJson('/api/email/verify/resend')->assertOk();
        $body = json_encode($resend->json());
        $this->assertStringNotContainsString('"code"', $body ?? '');

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);
        $this->assertTrue(Hash::check($code, \App\Models\EmailAuthCode::query()->latest('id')->first()->code_hash));

        $verify = $this->postJson('/api/email/verify', ['code' => $code])->assertOk();
        $this->assertStringNotContainsString($code, json_encode($verify->json()) ?: '');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_register_and_login_include_otp_cooldown_metadata(): void
    {
        Mail::fake();
        config(['otp.resend_cooldown_seconds' => 45]);

        $register = $this->postJson('/api/register', [
            'name' => 'OTP User',
            'email' => 'otp-register@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
            'locale' => 'en',
        ])->assertCreated();

        $register->assertJsonPath('needs_email_verification', true)
            ->assertJsonPath('otp.retry_after_seconds', 45)
            ->assertJsonStructure(['otp' => ['resend_available_at', 'expires_in_minutes']]);

        Mail::assertSent(AuthCodeMail::class, 1);

        // Second login while cooldown active should not send another mail.
        $this->postJson('/api/login', [
            'email' => 'otp-register@example.com',
            'password' => 'Password1!',
        ])->assertOk()
            ->assertJsonPath('needs_email_verification', true)
            ->assertJsonStructure(['otp' => ['retry_after_seconds', 'resend_available_at']]);

        Mail::assertSent(AuthCodeMail::class, 1);
    }

    public function test_password_reset_request_is_enumeration_safe_with_cooldown_fields(): void
    {
        Mail::fake();

        $unknown = $this->postJson('/api/forgot-password', [
            'email' => 'nobody@example.com',
        ])->assertOk();

        $unknown->assertJsonPath('message', __('auth.reset_code_sent'))
            ->assertJsonStructure(['retry_after_seconds', 'resend_available_at', 'expires_in_minutes']);

        $user = User::factory()->active()->create([
            'email' => 'reset-otp@example.com',
            'password' => 'Password1!',
        ]);

        $known = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $known->assertJsonPath('message', __('auth.reset_code_sent'))
            ->assertJsonStructure(['retry_after_seconds', 'resend_available_at', 'expires_in_minutes']);

        Mail::assertSent(AuthCodeMail::class, 1);
    }
}
