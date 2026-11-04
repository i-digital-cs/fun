<?php

namespace Tests\Feature;

use App\Mail\AuthCodeMail;
use App\Mail\BrevoDeliveryTestMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BrevoMailTestCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_default_mailer_remains_unchanged(): void
    {
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('array', env('MAIL_MAILER'));
        $this->assertArrayHasKey('brevo', config('mail.mailers'));
        $this->assertSame('smtp', config('mail.mailers.brevo.transport'));
        $this->assertNotSame('brevo', config('mail.default'));
    }

    public function test_command_rejects_missing_recipient(): void
    {
        Mail::fake();

        $exit = Artisan::call('funnity:mail-test-brevo');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Missing required --to=', Artisan::output());
        Mail::assertNothingSent();
    }

    public function test_command_rejects_invalid_recipient(): void
    {
        Mail::fake();

        $exit = Artisan::call('funnity:mail-test-brevo', ['--to' => 'not-an-email']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Invalid recipient', Artisan::output());
        Mail::assertNothingSent();
    }

    public function test_missing_brevo_credentials_produce_safe_error(): void
    {
        Mail::fake();

        config([
            'mail.mailers.brevo.username' => 'c13d0e001@smtp-brevo.com',
            'mail.mailers.brevo.password' => '',
            'mail.mailers.brevo.host' => 'smtp-relay.brevo.com',
        ]);

        $exit = Artisan::call('funnity:mail-test-brevo', ['--to' => 'owner@gmail.com']);
        $output = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('BREVO_MAIL_PASSWORD', $output);
        $this->assertStringNotContainsString('password=', strtolower($output));
        Mail::assertNothingSent();
        Mail::assertNotSent(AuthCodeMail::class);
    }

    public function test_command_uses_only_brevo_mailer_and_sends_one_message(): void
    {
        Mail::fake();

        config([
            'mail.default' => 'array',
            'mail.mailers.brevo.username' => 'brevo-test-user',
            'mail.mailers.brevo.password' => 'brevo-test-secret-value',
            'mail.mailers.brevo.host' => 'smtp-relay.brevo.com',
            'mail.mailers.brevo.from.address' => 'info@funnity.com',
            'mail.mailers.brevo.from.name' => 'Funnity',
        ]);

        $exit = Artisan::call('funnity:mail-test-brevo', ['--to' => 'owner@gmail.com']);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertSame('array', config('mail.default'));
        $this->assertStringContainsString('owner@gmail.com', $output);
        $this->assertStringContainsString('Mailer: brevo', $output);
        $this->assertStringNotContainsString('brevo-test-secret-value', $output);
        $this->assertStringNotContainsString('brevo-test-user', $output);

        Mail::assertSent(BrevoDeliveryTestMail::class, 1);
        Mail::assertSent(BrevoDeliveryTestMail::class, function (BrevoDeliveryTestMail $mail) {
            return $mail->hasTo('owner@gmail.com')
                && $mail->mailer === 'brevo';
        });
        Mail::assertNotSent(AuthCodeMail::class);
        Mail::assertSentCount(1);
    }

    public function test_command_does_not_trigger_otp_mailables(): void
    {
        Mail::fake();

        config([
            'mail.mailers.brevo.username' => 'brevo-test-user',
            'mail.mailers.brevo.password' => 'brevo-test-secret-value',
            'mail.mailers.brevo.host' => 'smtp-relay.brevo.com',
        ]);

        Artisan::call('funnity:mail-test-brevo', ['--to' => 'probe@gmail.com']);

        Mail::assertSent(BrevoDeliveryTestMail::class, 1);
        Mail::assertNotSent(AuthCodeMail::class);
    }

    public function test_transport_failure_redacts_credentials_from_output(): void
    {
        $secret = 'super-secret-brevo-password-xyz';
        $user = 'c13d0e001@smtp-brevo.com';

        config([
            'mail.mailers.brevo.username' => $user,
            'mail.mailers.brevo.password' => $secret,
            'mail.mailers.brevo.host' => 'smtp-relay.brevo.com',
        ]);

        $pending = Mockery::mock();
        $pending->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException("SMTP auth failed password={$secret} user={$user}"));

        $mailer = Mockery::mock();
        $mailer->shouldReceive('to')
            ->once()
            ->with('owner@gmail.com')
            ->andReturn($pending);

        Mail::shouldReceive('mailer')
            ->once()
            ->with('brevo')
            ->andReturn($mailer);

        $exit = Artisan::call('funnity:mail-test-brevo', ['--to' => 'owner@gmail.com']);
        $output = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('SMTP connection or submission failed', $output);
        $this->assertStringNotContainsString($secret, $output);
        $this->assertStringNotContainsString($user, $output);
        $this->assertStringContainsString('[redacted]', $output);
    }
}
