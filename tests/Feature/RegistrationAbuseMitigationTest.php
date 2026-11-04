<?php

namespace Tests\Feature;

use App\Contracts\Security\CaptchaVerifier;
use App\Enums\AccountStatus;
use App\Enums\OutboundOtpLane;
use App\Mail\AuthCodeMail;
use App\Models\User;
use App\Services\Auth\OutboundOtpBudget;
use App\Services\Security\TurnstileVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationAbuseMitigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('reg-ip:127.0.0.1');
        RateLimiter::clear('reg-ip-h:127.0.0.1');
        config([
            'registration.enabled' => true,
            'registration.global_per_hour' => 100,
            'registration.global_per_day' => 500,
            'registration.ip_per_day' => 100,
            'registration.email_per_hour' => 10,
            'registration.require_otp_budget' => true,
            'turnstile.enabled_web' => false,
            'turnstile.enabled_api' => false,
            'otp.outbound_per_hour' => 120,
            'otp.outbound_per_day' => 800,
        ]);
    }

    public function test_valid_api_registration_creates_user_and_queues_otp_mail(): void
    {
        $this->postJson('/api/register', $this->payload('ok@example.com'))
            ->assertCreated()
            ->assertJsonPath('needs_email_verification', true);

        $this->assertDatabaseHas('users', ['email' => 'ok@example.com']);
        Mail::assertSent(AuthCodeMail::class);
    }

    public function test_valid_web_json_registration_succeeds_without_turnstile_when_disabled(): void
    {
        $this->postJson('/register', $this->payload('webok@example.com'))
            ->assertOk()
            ->assertJsonStructure(['redirect', 'message']);

        $this->assertDatabaseHas('users', ['email' => 'webok@example.com']);
    }

    public function test_missing_captcha_rejected_when_api_flag_enabled(): void
    {
        config([
            'turnstile.enabled_api' => true,
            'turnstile.secret_key' => 'test-secret',
            'turnstile.site_key' => 'test-site',
        ]);

        $this->postJson('/api/register', $this->payload('nocap@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', __('auth.captcha_failed'));

        $this->assertDatabaseMissing('users', ['email' => 'nocap@example.com']);
        Mail::assertNothingSent();
    }

    public function test_invalid_captcha_rejected_without_user_or_mail(): void
    {
        config([
            'turnstile.enabled_api' => true,
            'turnstile.secret_key' => 'test-secret',
            'turnstile.expected_hostnames' => [],
        ]);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $this->postJson('/api/register', array_merge($this->payload('badcap@example.com'), [
            'cf-turnstile-response' => 'bad-token',
        ]))->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'badcap@example.com']);
        Mail::assertNothingSent();
    }

    public function test_reused_captcha_token_is_rejected(): void
    {
        config([
            'turnstile.enabled_api' => true,
            'turnstile.secret_key' => 'test-secret',
            'turnstile.expected_action' => 'register',
            'turnstile.expected_hostnames' => ['funnity.com'],
            'turnstile.token_reuse_ttl_seconds' => 300,
        ]);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'hostname' => 'funnity.com',
                'action' => 'register',
            ], 200),
        ]);

        $payload = array_merge($this->payload('reuse1@example.com'), [
            'cf-turnstile-response' => 'same-token-once',
        ]);
        $this->postJson('/api/register', $payload)->assertCreated();

        RateLimiter::clear('reg-ip:127.0.0.1');
        RateLimiter::clear('reg-ip-h:127.0.0.1');

        $this->postJson('/api/register', array_merge($this->payload('reuse2@example.com'), [
            'cf-turnstile-response' => 'same-token-once',
        ]))->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'reuse2@example.com']);
    }

    public function test_expired_or_hostname_mismatch_captcha_rejected(): void
    {
        config([
            'turnstile.enabled_api' => true,
            'turnstile.secret_key' => 'test-secret',
            'turnstile.expected_hostnames' => ['funnity.com'],
        ]);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'hostname' => 'evil.example',
                'action' => 'register',
            ], 200),
        ]);

        $this->postJson('/api/register', array_merge($this->payload('host@example.com'), [
            'cf-turnstile-response' => 'host-token',
        ]))->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'host@example.com']);
    }

    public function test_api_registration_unaffected_when_only_web_turnstile_enabled(): void
    {
        config([
            'turnstile.enabled_web' => true,
            'turnstile.enabled_api' => false,
            'turnstile.secret_key' => 'test-secret',
        ]);

        $this->postJson('/api/register', $this->payload('flutter@example.com'))
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'flutter@example.com']);
    }

    public function test_global_registration_budget_blocks_without_creating_user(): void
    {
        config(['registration.global_per_hour' => 1]);

        $this->postJson('/api/register', $this->payload('g1@example.com'))->assertCreated();

        RateLimiter::clear('reg-ip:127.0.0.1');
        RateLimiter::clear('reg-ip-h:127.0.0.1');

        $this->postJson('/api/register', $this->payload('g2@example.com'))
            ->assertStatus(429)
            ->assertJsonPath('message', __('auth.registration_unavailable'));

        $this->assertDatabaseMissing('users', ['email' => 'g2@example.com']);
        $this->assertSame(1, User::query()->where('email', 'g1@example.com')->count());
    }

    public function test_otp_budget_exhaustion_blocks_registration_before_user_create(): void
    {
        config([
            'otp.outbound_per_hour' => 1,
            'registration.require_otp_budget' => true,
        ]);

        $budget = app(OutboundOtpBudget::class);
        $this->assertTrue($budget->allow(OutboundOtpLane::AbuseProne));
        $budget->record(OutboundOtpLane::AbuseProne);
        $this->assertFalse($budget->allow(OutboundOtpLane::AbuseProne));

        $this->postJson('/api/register', $this->payload('otpblock@example.com'))
            ->assertStatus(429);

        $this->assertDatabaseMissing('users', ['email' => 'otpblock@example.com']);
        Mail::assertNothingSent();
    }

    public function test_kill_switch_blocks_registration(): void
    {
        config(['registration.enabled' => false]);

        $this->postJson('/api/register', $this->payload('kill@example.com'))
            ->assertStatus(429);

        $this->assertDatabaseMissing('users', ['email' => 'kill@example.com']);
    }

    public function test_verified_user_can_still_login(): void
    {
        $user = User::factory()->create([
            'email' => 'loginok@example.com',
            'password' => 'Password1!',
            'email_verified_at' => now(),
            'status' => AccountStatus::Active,
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_deactivated_user_login_rejected(): void
    {
        $user = User::factory()->deactivated()->create([
            'email' => 'gone@example.com',
            'password' => 'Password1!',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertStatus(422);
    }

    public function test_google_oauth_endpoint_still_validates_token(): void
    {
        $this->postJson('/api/auth/google', [
            'id_token' => 'not-a-real-token',
        ])->assertStatus(422);
    }

    public function test_turnstile_verifier_rejects_blank_and_marks_reuse(): void
    {
        config([
            'turnstile.secret_key' => 'sec',
            'turnstile.expected_hostnames' => [],
            'turnstile.expected_action' => 'register',
        ]);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'action' => 'register',
            ], 200),
        ]);

        /** @var TurnstileVerifier $verifier */
        $verifier = app(CaptchaVerifier::class);

        $this->assertFalse($verifier->verify(null, 'register')->ok);
        $first = $verifier->verify('tok-abc', 'register');
        $this->assertTrue($first->ok);
        $this->assertFalse($verifier->verify('tok-abc', 'register')->ok);
    }

    public function test_rate_limiter_still_trips_after_burst(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', $this->payload("burst{$i}@example.com"))
                ->assertCreated();
        }

        $this->postJson('/api/register', $this->payload('burstx@example.com'))
            ->assertStatus(429);
    }

    /**
     * @return array<string, string>
     */
    private function payload(string $email): array
    {
        return [
            'name' => 'Reg User',
            'email' => $email,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
            'locale' => 'en',
        ];
    }
}
