<?php

namespace Tests\Feature;

use App\Mail\AuthCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiRegistrationHardeningTest extends TestCase
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
            'registration.ip_per_hour' => 50,
            'registration.ip_per_day' => 100,
            'registration.api_ip_per_day' => 100,
            'registration.email_per_hour' => 20,
            'registration.require_otp_budget' => true,
            'registration.honeypot_enabled' => true,
            'registration.reject_digit_names' => true,
            'registration.reject_disposable_domains' => false,
            'turnstile.enabled_web' => true,
            'turnstile.enabled_api' => false,
            'turnstile.secret_key' => 'test-secret',
            'turnstile.site_key' => 'test-site',
            'otp.outbound_per_hour' => 120,
            'otp.outbound_per_day' => 800,
        ]);
    }

    public function test_existing_flutter_api_body_still_registers_without_captcha(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Play Tester',
            'email' => 'play.tester@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
            'locale' => 'en',
        ])->assertCreated()
            ->assertJsonPath('needs_email_verification', true)
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'play.tester@example.com']);
        Mail::assertSent(AuthCodeMail::class);
    }

    public function test_api_honeypot_filled_blocks_without_user_or_otp(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Bot User',
            'email' => 'honeypot@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
            'website' => 'http://spam.example',
        ])->assertStatus(429);

        $this->assertDatabaseMissing('users', ['email' => 'honeypot@example.com']);
        Mail::assertNothingSent();
    }

    public function test_api_digit_only_name_blocked(): void
    {
        $this->postJson('/api/register', [
            'name' => '4829103',
            'email' => 'digits@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'male',
        ])->assertStatus(429);

        $this->assertDatabaseMissing('users', ['email' => 'digits@example.com']);
        Mail::assertNothingSent();
    }

    public function test_short_human_names_still_allowed(): void
    {
        $this->postJson('/api/register', [
            'name' => 'as',
            'email' => 'shortname@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
        ])->assertCreated();
    }

    public function test_api_ip_day_budget_is_channel_aware(): void
    {
        config(['registration.api_ip_per_day' => 1, 'registration.ip_per_day' => 100]);

        $this->postJson('/api/register', $this->payload('api1@example.com'))->assertCreated();

        RateLimiter::clear('reg-ip:127.0.0.1');
        RateLimiter::clear('reg-ip-h:127.0.0.1');

        $this->postJson('/api/register', $this->payload('api2@example.com'))
            ->assertStatus(429);

        $this->assertDatabaseMissing('users', ['email' => 'api2@example.com']);
    }

    public function test_web_turnstile_still_required_when_enabled(): void
    {
        $this->postJson('/register', $this->payload('webneedscap@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', __('auth.captcha_failed'));

        $this->assertDatabaseMissing('users', ['email' => 'webneedscap@example.com']);
    }

    public function test_duplicate_email_still_validation_error(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', $this->payload('taken@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_google_oauth_route_still_present(): void
    {
        $this->postJson('/api/auth/google', ['id_token' => 'x'])
            ->assertStatus(422);
    }

    /**
     * @return array<string, string>
     */
    private function payload(string $email): array
    {
        return [
            'name' => 'API User',
            'email' => $email,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
            'locale' => 'en',
        ];
    }
}
