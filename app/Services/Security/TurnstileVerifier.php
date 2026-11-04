<?php

namespace App\Services\Security;

use App\Contracts\Security\CaptchaVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cloudflare Turnstile Siteverify client.
 *
 * Fail closed when the channel is enabled but credentials/network fail.
 * Tokens are single-use within the configured reuse TTL.
 */
class TurnstileVerifier implements CaptchaVerifier
{
    public function verify(?string $token, string $action, ?string $remoteIp = null): CaptchaVerificationResult
    {
        $token = is_string($token) ? trim($token) : '';

        if ($token === '') {
            return CaptchaVerificationResult::failure('missing_token');
        }

        $secret = (string) config('turnstile.secret_key', '');
        if ($secret === '') {
            Log::warning('turnstile_misconfigured', ['reason' => 'missing_secret']);

            return CaptchaVerificationResult::failure('misconfigured');
        }

        $tokenHash = hash('sha256', $token);
        $reuseKey = 'turnstile:used:'.$tokenHash;

        if (Cache::has($reuseKey)) {
            Log::warning('turnstile_token_reused', [
                'token_hash_prefix' => substr($tokenHash, 0, 12),
                'action' => $action,
            ]);

            return CaptchaVerificationResult::failure('token_reused');
        }

        try {
            $payload = [
                'secret' => $secret,
                'response' => $token,
            ];
            if (is_string($remoteIp) && $remoteIp !== '') {
                $payload['remoteip'] = $remoteIp;
            }

            $response = Http::asForm()
                ->timeout(max(1, (int) config('turnstile.timeout_seconds', 5)))
                ->post((string) config('turnstile.verify_url'), $payload);
        } catch (Throwable $exception) {
            Log::warning('turnstile_verify_transport_failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return CaptchaVerificationResult::failure('transport_error');
        }

        if (! $response->successful()) {
            Log::warning('turnstile_verify_http_failed', [
                'status' => $response->status(),
            ]);

            return CaptchaVerificationResult::failure('http_error');
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        $success = (bool) ($body['success'] ?? false);
        /** @var list<string> $errorCodes */
        $errorCodes = array_values(array_map('strval', $body['error-codes'] ?? []));

        if (! $success) {
            Log::warning('turnstile_verify_rejected', [
                'error_codes' => $errorCodes,
                'action' => $action,
            ]);

            return CaptchaVerificationResult::failure('rejected', $errorCodes);
        }

        $hostname = isset($body['hostname']) && is_string($body['hostname']) ? $body['hostname'] : null;
        $responseAction = isset($body['action']) && is_string($body['action']) ? $body['action'] : null;

        $expectedHostnames = config('turnstile.expected_hostnames', []);
        if (is_array($expectedHostnames) && $expectedHostnames !== []) {
            if ($hostname === null || ! in_array($hostname, $expectedHostnames, true)) {
                Log::warning('turnstile_hostname_mismatch', [
                    'hostname' => $hostname,
                ]);

                return CaptchaVerificationResult::failure('hostname_mismatch', $errorCodes);
            }
        }

        $expectedAction = (string) config('turnstile.expected_action', $action);
        if ($expectedAction !== '' && $responseAction !== null && $responseAction !== $expectedAction) {
            Log::warning('turnstile_action_mismatch', [
                'expected' => $expectedAction,
                'got' => $responseAction,
            ]);

            return CaptchaVerificationResult::failure('action_mismatch', $errorCodes);
        }

        // Mark used only after successful Siteverify (atomic add → reject races).
        $ttl = max(30, (int) config('turnstile.token_reuse_ttl_seconds', 300));
        if (! Cache::add($reuseKey, 1, now()->addSeconds($ttl))) {
            return CaptchaVerificationResult::failure('token_reused');
        }

        return CaptchaVerificationResult::success($hostname, $responseAction);
    }
}
