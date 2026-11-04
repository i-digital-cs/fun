<?php

namespace Tests\Feature;

use App\Enums\EmailAuthPurpose;
use App\Enums\OutboundOtpLane;
use App\Exceptions\DomainException;
use App\Mail\AuthCodeMail;
use App\Models\User;
use App\Services\Auth\EmailAuthCodeService;
use App\Services\Auth\OutboundOtpBudget;
use App\Services\Auth\PasswordResetTokenService;
use App\Support\EmailNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OtpSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_normalizer_trims_and_lowercases(): void
    {
        $this->assertSame('user@example.com', EmailNormalizer::normalize('  User@Example.COM  '));
        $this->assertSame('', EmailNormalizer::normalize(null));
    }

    public function test_registration_normalizes_email_casing(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Case User',
            'email' => '  Case.User@Example.COM ',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
            'locale' => 'en',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'case.user@example.com',
        ]);

        $this->postJson('/api/register', [
            'name' => 'Dup',
            'email' => 'CASE.USER@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
        ])->assertStatus(422);
    }

    public function test_registration_ip_burst_is_rate_limited(): void
    {
        Mail::fake();
        RateLimiter::clear('reg-ip:127.0.0.1');
        RateLimiter::clear('reg-ip-h:127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'name' => "Burst {$i}",
                'email' => "burst{$i}@example.com",
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'sex' => 'mix',
            ])->assertCreated();
        }

        $this->postJson('/api/register', [
            'name' => 'Burst blocked',
            'email' => 'burst-blocked@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
        ])->assertStatus(429);
    }

    public function test_otp_resend_respects_per_email_rate_limit_via_normalized_key(): void
    {
        Mail::fake();
        config(['otp.resend_cooldown_seconds' => 1]);

        $user = User::factory()->unverified()->active()->create([
            'email' => 'limit-me@example.com',
        ]);
        Sanctum::actingAs($user);

        // Clear IP/user buckets so email key is the binding constraint under test.
        RateLimiter::clear('otp-ip:127.0.0.1');
        RateLimiter::clear('otp-ip-h:127.0.0.1');
        RateLimiter::clear('otp-uid:'.$user->id);
        RateLimiter::clear('otp-uid-h:'.$user->id);

        $hash = hash('sha256', EmailNormalizer::normalize('limit-me@example.com'));
        RateLimiter::clear('otp-em:'.$hash);
        RateLimiter::clear('otp-em-h:'.$hash);

        // Forgot-password uses request email for the email-based limiter.
        for ($i = 0; $i < 3; $i++) {
            Cache::forget('otp:cooldown:'.$user->id.':'.EmailAuthPurpose::PasswordReset->value);
            $this->postJson('/api/forgot-password', [
                'email' => 'Limit-Me@Example.com',
            ])->assertOk();
        }

        Cache::forget('otp:cooldown:'.$user->id.':'.EmailAuthPurpose::PasswordReset->value);
        $this->postJson('/api/forgot-password', [
            'email' => 'LIMIT-ME@example.com',
        ])->assertStatus(429);
    }

    public function test_outbound_budget_blocks_further_sends_without_permanent_deny(): void
    {
        Mail::fake();
        config([
            'otp.outbound_per_hour' => 2,
            'otp.outbound_per_day' => 2,
            'otp.resend_cooldown_seconds' => 1,
        ]);

        Cache::flush();

        $service = app(EmailAuthCodeService::class);
        $budget = app(OutboundOtpBudget::class);

        $userA = User::factory()->unverified()->active()->create(['email' => 'a@example.com']);
        $userB = User::factory()->unverified()->active()->create(['email' => 'b@example.com']);
        $userC = User::factory()->unverified()->active()->create(['email' => 'c@example.com']);

        $service->issueAndSend($userA, EmailAuthPurpose::EmailVerify, OutboundOtpLane::AbuseProne);
        $service->issueAndSend($userB, EmailAuthPurpose::EmailVerify, OutboundOtpLane::AbuseProne);

        $this->assertFalse($budget->allow(OutboundOtpLane::AbuseProne));

        try {
            $service->issueAndSend($userC, EmailAuthPurpose::EmailVerify, OutboundOtpLane::AbuseProne);
            $this->fail('Expected DomainException when outbound budget exceeded');
        } catch (DomainException $exception) {
            $this->assertSame(__('auth.code_delivery_failed'), $exception->getMessage());
        }

        Mail::assertSent(AuthCodeMail::class, 2);

        // Rolling window: after counters reset, sends resume.
        Cache::flush();
        $this->assertTrue($budget->allow(OutboundOtpLane::AbuseProne));
        $service->issueAndSend($userC, EmailAuthPurpose::EmailVerify, OutboundOtpLane::AbuseProne);
        Mail::assertSent(AuthCodeMail::class, 3);
    }

    public function test_abuse_lane_exhaustion_does_not_block_member_resend_lane(): void
    {
        Mail::fake();
        config([
            'otp.outbound_per_hour' => 1,
            'otp.outbound_per_day' => 1,
            'otp.outbound_member_per_hour' => 5,
            'otp.outbound_member_per_day' => 5,
            'otp.resend_cooldown_seconds' => 1,
        ]);
        Cache::flush();

        $service = app(EmailAuthCodeService::class);
        $budget = app(OutboundOtpBudget::class);

        $attacker = User::factory()->unverified()->active()->create(['email' => 'attack@example.com']);
        $member = User::factory()->unverified()->active()->create(['email' => 'member@example.com']);

        $service->issueAndSend($attacker, EmailAuthPurpose::EmailVerify, OutboundOtpLane::AbuseProne);
        $this->assertFalse($budget->allow(OutboundOtpLane::AbuseProne));
        $this->assertTrue($budget->allow(OutboundOtpLane::Member));

        $service->issueAndSend($member, EmailAuthPurpose::EmailVerify, OutboundOtpLane::Member);
        Mail::assertSent(AuthCodeMail::class, 2);
    }

    public function test_otp_verify_page_has_exactly_one_visible_resend_control(): void
    {
        $user = User::factory()->unverified()->active()->create([
            'email' => 'ui-otp@example.com',
            'password' => 'Password1!',
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee(__('ui.access_resend_prompt'), false);

        $html = $this->actingAs($user)->get(route('verification.notice'))->getContent();
        $this->assertSame(1, substr_count($html, 'data-auth-resend'));
        $this->assertSame(1, substr_count($html, 'data-resend-cooldown'));
        $this->assertMatchesRegularExpression('/data-resend-cooldown[^>]*class="[^"]*sr-only/', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/data-resend-cooldown[^>]*class="[^"]*\bmt-1\b/',
            $html,
        );
    }

    public function test_password_reset_masks_delivery_failure_and_logs_internally(): void
    {
        Mail::fake();
        Event::fake([MessageLogged::class]);

        $user = User::factory()->active()->create([
            'email' => 'reset-fail@example.com',
            'password' => 'Password1!',
        ]);

        config([
            'otp.outbound_per_hour' => 1,
            'otp.outbound_per_day' => 1,
        ]);
        Cache::flush();

        $codes = app(EmailAuthCodeService::class);
        $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);

        $payload = app(PasswordResetTokenService::class)->requestCode($user->email);

        $this->assertArrayHasKey('retry_after_seconds', $payload);
        $this->assertArrayHasKey('resend_available_at', $payload);
        $this->assertArrayHasKey('expires_in_minutes', $payload);

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && $event->message === 'password_reset_delivery_masked'
                && isset($event->context['user_id'])
                && ! isset($event->context['email'])
                && ! isset($event->context['code']);
        });
    }

    public function test_password_reset_unknown_email_stays_generic(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'missing-user@example.com',
        ])->assertOk();

        $response->assertJsonPath('message', __('auth.reset_code_sent'))
            ->assertJsonStructure(['retry_after_seconds', 'resend_available_at', 'expires_in_minutes']);

        Mail::assertNothingSent();
    }

    public function test_x_forwarded_for_does_not_bypass_ip_registration_limit(): void
    {
        Mail::fake();
        RateLimiter::clear('reg-ip:127.0.0.1');
        RateLimiter::clear('reg-ip-h:127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders(['X-Forwarded-For' => '203.0.113.'.($i + 1)])
                ->postJson('/api/register', [
                    'name' => "Spoof {$i}",
                    'email' => "spoof{$i}@example.com",
                    'password' => 'Password1!',
                    'password_confirmation' => 'Password1!',
                    'sex' => 'mix',
                ])->assertCreated();
        }

        $this->withHeaders(['X-Forwarded-For' => '198.51.100.50'])
            ->postJson('/api/register', [
                'name' => 'Spoof blocked',
                'email' => 'spoof-blocked@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'sex' => 'mix',
            ])->assertStatus(429);
    }

    public function test_expired_otp_cannot_be_reused(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->active()->create([
            'email' => 'expired@example.com',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify/resend')->assertOk();

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        \App\Models\EmailAuthCode::query()->where('user_id', $user->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/email/verify', ['code' => $code])
            ->assertStatus(422)
            ->assertJsonPath('message', __('auth.code_expired'));
    }

    public function test_successful_otp_is_single_use(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->active()->create([
            'email' => 'once@example.com',
        ]);

        $service = app(EmailAuthCodeService::class);
        $service->issueAndSend($user, EmailAuthPurpose::EmailVerify);

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $service->verify($user, EmailAuthPurpose::EmailVerify, $code);

        try {
            $service->verify($user, EmailAuthPurpose::EmailVerify, $code);
            $this->fail('Expected DomainException when reusing a consumed OTP');
        } catch (DomainException $exception) {
            $this->assertSame(__('auth.code_expired'), $exception->getMessage());
        }
    }

    public function test_api_responses_never_include_plaintext_otp(): void
    {
        Mail::fake();

        $register = $this->postJson('/api/register', [
            'name' => 'Leak Check',
            'email' => 'leak-check@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
        ])->assertCreated();

        $code = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);
        $encoded = json_encode($register->json()) ?: '';
        $this->assertStringNotContainsString($code, $encoded);
        $this->assertStringNotContainsString('"code":', $encoded);
    }
}
