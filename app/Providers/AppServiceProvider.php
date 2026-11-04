<?php

namespace App\Providers;

use App\Contracts\Security\CaptchaVerifier;
use App\Services\Security\TurnstileVerifier;
use App\Support\EmailNormalizer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CaptchaVerifier::class, TurnstileVerifier::class);
    }

    public function boot(): void
    {
        // Keep generated links on APP_URL even if the request hit /public/...
        $appUrl = config('app.url');
        if (is_string($appUrl) && $appUrl !== '') {
            URL::forceRootUrl(rtrim($appUrl, '/'));
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        View::composer('*', function ($view): void {
            $view->with('siteName', site_display_name());
            $view->with('siteLogoUrl', site_logo_url());
        });

        // Login / generic auth (not registration).
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by('auth-ip:'.$request->ip());
        });

        // Registration: IP + normalized-email budgets (shared Web/API limiter name).
        RateLimiter::for('auth-register', function (Request $request) {
            $limits = [
                Limit::perMinute(5)->by('reg-ip:'.$request->ip()),
                Limit::perHour(20)->by('reg-ip-h:'.$request->ip()),
            ];

            $email = $request->input('email');
            if (is_string($email) && trim($email) !== '') {
                $hash = hash('sha256', EmailNormalizer::normalize($email));
                $limits[] = Limit::perMinute(2)->by('reg-em:'.$hash);
                $limits[] = Limit::perHour(5)->by('reg-em-h:'.$hash);
            }

            return $limits;
        });

        // OTP issuance / resend: IP + normalized email (+ user id when authenticated).
        RateLimiter::for('auth-codes', function (Request $request) {
            $limits = [
                Limit::perMinute(5)->by('otp-ip:'.$request->ip()),
                Limit::perHour(40)->by('otp-ip-h:'.$request->ip()),
            ];

            $email = $request->input('email');
            if (is_string($email) && trim($email) !== '') {
                $hash = hash('sha256', EmailNormalizer::normalize($email));
                $limits[] = Limit::perMinute(3)->by('otp-em:'.$hash);
                $limits[] = Limit::perHour(10)->by('otp-em-h:'.$hash);
            }

            if ($request->user() !== null) {
                $limits[] = Limit::perMinute(3)->by('otp-uid:'.$request->user()->id);
                $limits[] = Limit::perHour(12)->by('otp-uid-h:'.$request->user()->id);
            }

            return $limits;
        });

        // OTP verification attempts (separate from issuance spam controls).
        RateLimiter::for('auth-code-verify', function (Request $request) {
            $key = $request->user()?->id
                ? 'otp-v-uid:'.$request->user()->id
                : 'otp-v-ip:'.$request->ip();

            return [
                Limit::perMinute(10)->by($key),
                Limit::perHour(40)->by($key.'-h'),
            ];
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(5)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('messages', function (Request $request) {
            return Limit::perMinute(20)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('reports', function (Request $request) {
            return Limit::perMinute(5)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('ratings', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('joins', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('blocks', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?: $request->ip()));
        });
    }
}
