<?php

namespace Tests\Feature;

use App\Enums\EmailAuthPurpose;
use App\Enums\OutboundOtpLane;
use App\Mail\AuthCodeMail;
use App\Models\User;
use App\Services\Admin\SiteSettingsService;
use App\Services\Auth\EmailAuthCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TransactionalEmailV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verify_mail_with_english_subject_and_png_logo(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Alex Example',
            'email' => 'alex-email-v2@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'sex' => 'mix',
            'locale' => 'en',
        ])->assertCreated();

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) {
            $html = $mail->render();
            $envelope = $mail->envelope();

            $this->assertSame(
                __('auth.mail_verify_subject', ['app' => $mail->siteName]),
                $envelope->subject,
            );
            $this->assertSame('register', $mail->mailContext);
            $this->assertStringContainsString('branding/funnity-mark-email.png', $html);
            $this->assertStringNotContainsString('.svg', $html);
            $this->assertStringContainsString('dir="ltr"', $html);
            $this->assertStringContainsString('lang="en"', $html);
            $this->assertMatchesRegularExpression('/\b\d{4}\b/', $html);
            $this->assertStringContainsString((string) config('otp.expires_minutes', 15), $html);
            $this->assertStringContainsString(__('auth.mail_verify_heading'), $html);

            return $mail->purpose === EmailAuthPurpose::EmailVerify->value
                && $mail->hasTo('alex-email-v2@example.com');
        });
    }

    public function test_login_otp_uses_sign_in_copy_with_email_verify_purpose(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->active()->create([
            'email' => 'login-email-v2@example.com',
            'password' => Hash::make('Password1!'),
            'locale' => 'en',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertOk()
            ->assertJsonPath('needs_email_verification', true);

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) {
            $html = $mail->render();

            $this->assertSame(EmailAuthPurpose::EmailVerify->value, $mail->purpose);
            $this->assertSame('login', $mail->mailContext);
            $this->assertSame(
                __('auth.mail_login_subject', ['app' => $mail->siteName]),
                $mail->envelope()->subject,
            );
            $this->assertStringContainsString(__('auth.mail_login_heading'), $html);
            $this->assertStringContainsString(__('auth.mail_login_body'), $html);
            $this->assertStringNotContainsString(__('auth.mail_verify_heading'), $html);

            return true;
        });
    }

    public function test_password_reset_mail_uses_reset_copy(): void
    {
        Mail::fake();

        $user = User::factory()->active()->create([
            'email' => 'reset-email-v2@example.com',
            'password' => 'Password1!',
            'locale' => 'en',
        ]);

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) {
            $html = $mail->render();
            $this->assertSame(
                __('auth.mail_reset_subject', ['app' => $mail->siteName]),
                $mail->envelope()->subject,
            );
            $this->assertStringContainsString(__('auth.mail_reset_heading', ['app' => $mail->siteName]), $html);
            $this->assertSame(EmailAuthPurpose::PasswordReset->value, $mail->purpose);

            return true;
        });
    }

    public function test_email_change_mail_uses_email_change_copy(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->active()->create([
            'email' => 'change-before@example.com',
            'locale' => 'en',
        ]);

        app(EmailAuthCodeService::class)->issueAndSend(
            $user,
            EmailAuthPurpose::EmailVerify,
            OutboundOtpLane::Member,
            mailContext: 'email_change',
        );

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) {
            $html = $mail->render();
            $this->assertSame(EmailAuthPurpose::EmailVerify->value, $mail->purpose);
            $this->assertSame('email_change', $mail->mailContext);
            $this->assertSame(
                __('auth.mail_email_change_subject', ['app' => $mail->siteName]),
                $mail->envelope()->subject,
            );
            $this->assertStringContainsString(__('auth.mail_email_change_heading'), $html);

            return true;
        });
    }

    public function test_arabic_mail_renders_rtl_and_arabic_copy(): void
    {
        $user = User::factory()->unverified()->active()->create([
            'name' => 'سارة',
            'email' => 'sara-ar@example.com',
            'locale' => 'ar',
        ]);

        $mail = new AuthCodeMail(
            user: $user,
            code: '4821',
            purpose: EmailAuthPurpose::EmailVerify->value,
            siteName: 'Funnity',
            logoUrl: app(SiteSettingsService::class)->emailLogoUrl(),
            expiresMinutes: 15,
            mailContext: 'verify',
        );

        $html = $mail->render();
        $text = view('emails.auth-code-text', [
            'greeting' => $mail->greetingLine('ar'),
            'code' => '4821',
            'purpose' => EmailAuthPurpose::EmailVerify->value,
            'mailContext' => 'verify',
            'siteName' => 'Funnity',
            'expiresMinutes' => 15,
        ])->render();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('تأكيد بريدك الإلكتروني', $html);
        $this->assertStringContainsString('4821', $html);
        $this->assertStringContainsString('سارة', $html);
        $this->assertStringContainsString('مرحباً سارة،', $html);
        $this->assertStringContainsString('4821', $text);
        $this->assertStringContainsString('مرحباً سارة،', $text);
        $this->assertStringContainsString('تأكيد', $mail->envelope()->subject);
    }

    public function test_english_mail_escapes_user_controlled_name(): void
    {
        $user = User::factory()->unverified()->active()->create([
            'name' => '<script>alert(1)</script>',
            'email' => 'escape-me@example.com',
            'locale' => 'en',
        ]);

        $mail = new AuthCodeMail(
            user: $user,
            code: '9001',
            purpose: EmailAuthPurpose::EmailVerify->value,
            siteName: 'Funnity',
            logoUrl: app(SiteSettingsService::class)->emailLogoUrl(),
            expiresMinutes: 15,
        );

        $html = $mail->render();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('9001', $html);
    }

    public function test_email_logo_asset_is_public_png(): void
    {
        $url = app(SiteSettingsService::class)->emailLogoUrl();
        $this->assertStringContainsString('funnity-mark-email.png', $url);
        $this->assertFileExists(public_path('branding/funnity-mark-email.png'));
    }

    public function test_plain_text_alternative_includes_code_without_html(): void
    {
        $user = User::factory()->unverified()->active()->create([
            'locale' => 'en',
            'email' => 'text-alt@example.com',
        ]);

        $mail = new AuthCodeMail(
            user: $user,
            code: '7710',
            purpose: EmailAuthPurpose::PasswordReset->value,
            siteName: 'Funnity',
            logoUrl: null,
            expiresMinutes: 15,
        );

        $text = view('emails.auth-code-text', [
            'greeting' => $mail->greetingLine('en'),
            'code' => '7710',
            'purpose' => EmailAuthPurpose::PasswordReset->value,
            'mailContext' => 'password_reset',
            'siteName' => 'Funnity',
            'expiresMinutes' => 15,
        ])->render();
        $this->assertStringContainsString('7710', $text);
        $this->assertStringNotContainsString('<table', $text);
        $this->assertStringContainsString('Reset your password', $text);
        $this->assertStringContainsString($mail->greetingLine('en'), $text);
    }

    public function test_greeting_preserves_valid_english_and_short_names(): void
    {
        $alex = User::factory()->unverified()->active()->create([
            'name' => 'Alex Riviera',
            'email' => 'alex-greet@example.com',
            'locale' => 'en',
        ]);
        $short = User::factory()->unverified()->active()->create([
            'name' => 'as',
            'email' => 'short-greet@example.com',
            'locale' => 'en',
        ]);

        $alexMail = new AuthCodeMail($alex, '1111', EmailAuthPurpose::EmailVerify->value, 'Funnity', null, 15);
        $shortMail = new AuthCodeMail($short, '1111', EmailAuthPurpose::EmailVerify->value, 'Funnity', null, 15);

        $this->assertSame('Hi Alex Riviera,', $alexMail->greetingLine('en'));
        $this->assertStringContainsString('Hi Alex Riviera,', $alexMail->render());
        $this->assertSame('Hi as,', $shortMail->greetingLine('en'));
        $this->assertStringContainsString('Hi as,', $shortMail->render());
        $this->assertSame('as', $shortMail->displayName());
    }

    public function test_greeting_uses_neutral_copy_for_missing_or_whitespace_names(): void
    {
        $blank = User::factory()->unverified()->active()->create([
            'name' => '   ',
            'email' => 'blank-greet@example.com',
            'locale' => 'en',
        ]);
        $blank->name = '   ';

        $mail = new AuthCodeMail($blank, '2222', EmailAuthPurpose::EmailVerify->value, 'Funnity', null, 15);
        $this->assertNull($mail->displayName());
        $this->assertSame('Hi,', $mail->greetingLine('en'));
        $this->assertStringContainsString('Hi,', $mail->render());
        $this->assertStringNotContainsString('Hi  ,', $mail->render());

        $this->assertNull(AuthCodeMail::usableDisplayName(null));
        $this->assertNull(AuthCodeMail::usableDisplayName(''));
        $this->assertNull(AuthCodeMail::usableDisplayName(" \t\n "));
    }

    public function test_greeting_supports_arabic_named_and_neutral_forms(): void
    {
        $named = User::factory()->unverified()->active()->create([
            'name' => 'سارة أحمد',
            'email' => 'sara-greet@example.com',
            'locale' => 'ar',
        ]);
        $blank = User::factory()->unverified()->active()->create([
            'name' => 'x',
            'email' => 'blank-ar-greet@example.com',
            'locale' => 'ar',
        ]);
        $blank->name = '  ';

        $namedMail = new AuthCodeMail($named, '3333', EmailAuthPurpose::EmailVerify->value, 'Funnity', null, 15);
        $blankMail = new AuthCodeMail($blank, '3333', EmailAuthPurpose::EmailVerify->value, 'Funnity', null, 15);

        $this->assertSame('مرحباً سارة أحمد،', $namedMail->greetingLine('ar'));
        $this->assertStringContainsString('dir="rtl"', $namedMail->render());
        $this->assertSame('مرحباً،', $blankMail->greetingLine('ar'));
    }

    public function test_resend_still_uses_auth_code_mail_and_png_logo(): void
    {
        Mail::fake();
        config(['otp.resend_cooldown_seconds' => 1]);

        $user = User::factory()->unverified()->active()->create([
            'email' => 'resend-v2@example.com',
            'locale' => 'en',
        ]);

        app(EmailAuthCodeService::class)->issueAndSend($user, EmailAuthPurpose::EmailVerify);

        Mail::assertSent(AuthCodeMail::class, 1);
        Mail::assertSent(AuthCodeMail::class, fn (AuthCodeMail $mail) => str_contains($mail->logoUrl ?? '', 'funnity-mark-email.png'));
    }

    public function test_contrast_styles_present_in_html(): void
    {
        $user = User::factory()->unverified()->active()->create([
            'locale' => 'en',
            'email' => 'contrast@example.com',
        ]);

        $html = (new AuthCodeMail(
            user: $user,
            code: '1122',
            purpose: EmailAuthPurpose::EmailVerify->value,
            siteName: 'Funnity',
            logoUrl: app(SiteSettingsService::class)->emailLogoUrl(),
            expiresMinutes: 15,
        ))->render();

        $this->assertStringContainsString('width="88"', $html);
        $this->assertStringContainsString('color:#475569', $html);
        $this->assertStringContainsString('color:#64748B', $html);
    }
}
