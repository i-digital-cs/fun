<?php

namespace App\Services\Auth;

use App\Contracts\Security\CaptchaVerifier;
use App\Exceptions\RegistrationBlockedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Pre-create registration gate:
 * Turnstile (channel-flagged) → observable signals → abuse budgets/lock.
 */
class RegistrationSecurityGate
{
    public function __construct(
        private readonly CaptchaVerifier $captcha,
        private readonly RegistrationSignalInspector $signals,
        private readonly RegistrationAbuseGuard $abuseGuard,
    ) {
    }

    /**
     * @return \Illuminate\Contracts\Cache\Lock
     *
     * @throws RegistrationBlockedException
     */
    public function authorize(Request $request, string $email, string $name = ''): \Illuminate\Contracts\Cache\Lock
    {
        $this->assertCaptcha($request);
        $this->signals->assertAcceptable($request, $email, $name);

        return $this->abuseGuard->assertMayRegister(
            (string) $request->ip(),
            $email,
            $this->channel($request),
        );
    }

    public function recordAccepted(Request $request, string $email): void
    {
        $this->abuseGuard->recordAccepted((string) $request->ip(), $email);
    }

    /**
     * @throws RegistrationBlockedException
     */
    private function assertCaptcha(Request $request): void
    {
        $channel = $this->channel($request);
        $enabled = (bool) config('turnstile.enabled_'.$channel, false);

        if (! $enabled) {
            return;
        }

        $token = $request->input('cf-turnstile-response')
            ?? $request->input('turnstile_token')
            ?? $request->header('X-Turnstile-Token');

        $result = $this->captcha->verify(
            is_string($token) ? $token : null,
            (string) config('turnstile.expected_action', 'register'),
            $request->ip(),
        );

        if ($result->ok) {
            return;
        }

        Log::warning('registration_captcha_rejected', [
            'channel' => $channel,
            'reason' => $result->reason,
            'error_codes' => $result->errorCodes,
            'ip_fp' => substr(hash('sha256', (string) $request->ip().'|'.config('app.key')), 0, 16),
        ]);

        throw new RegistrationBlockedException(
            message: __('auth.captcha_failed'),
            status: 422,
            reasonCode: 'captcha_'.$result->reason,
        );
    }

    /**
     * @return 'web'|'api'
     */
    private function channel(Request $request): string
    {
        return $request->is('api/*') || $request->is('api') ? 'api' : 'web';
    }
}
