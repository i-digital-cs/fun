<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmPushService
{
    /** @var list<string> */
    private const PERMANENTLY_INVALID_ERROR_CODES = [
        'UNREGISTERED',
        'NOT_FOUND',
        'INVALID_ARGUMENT',
        'SENDER_ID_MISMATCH',
    ];

    /**
     * @param  array{title: string, body: string, data?: array<string, string>}  $payload
     */
    public function sendToUser(User $user, array $payload): void
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->all();

        if ($tokens === []) {
            return;
        }

        if (! $this->isConfigured()) {
            Log::info('fcm_skipped_not_configured', [
                'user_id' => $user->id,
                'token_count' => count($tokens),
                'title' => $payload['title'] ?? null,
            ]);

            return;
        }

        foreach ($tokens as $token) {
            $this->sendToToken((string) $token, $payload);
        }
    }

    /**
     * @param  array{title: string, body: string, data?: array<string, string>}  $payload
     */
    public function sendToToken(string $token, array $payload): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $projectId = (string) config('services.firebase.project_id');
        $accessToken = $this->accessToken();
        if ($accessToken === null || $projectId === '') {
            return;
        }

        $capturedAt = now();

        try {
            $data = array_map('strval', $payload['data'] ?? []);
            $data['title'] = (string) ($payload['title'] ?? '');
            $data['body'] = (string) ($payload['body'] ?? '');

            $androidNotification = [
                'icon' => 'ic_stat_funity',
                'color' => '#4F46E5',
                'channel_id' => 'funnity_alerts',
            ];

            $logoUrl = app(\App\Services\Admin\SiteSettingsService::class)->logoUrl();
            // FCM image must be a bitmap URL (PNG/JPEG/WebP) — skip SVG.
            if (is_string($logoUrl) && $logoUrl !== '' && ! str_ends_with(strtolower(parse_url($logoUrl, PHP_URL_PATH) ?: ''), '.svg')) {
                $androidNotification['image'] = $logoUrl;
            }

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $payload['title'],
                            'body' => $payload['body'],
                        ],
                        'data' => $data,
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => $androidNotification,
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                $errorCode = $this->extractErrorCode($response);
                $classification = $this->classifyFailure($response->status(), $errorCode);

                Log::warning('fcm_send_failed', [
                    'status' => $response->status(),
                    'error_code' => $errorCode,
                    'classification' => $classification,
                    'token_fp' => $this->tokenFingerprint($token),
                    'body' => $response->json() ?? $response->body(),
                ]);

                if ($classification === 'invalidate_token') {
                    $this->invalidateTokenIfUnchanged($token, $capturedAt);
                }

                return;
            }

            DeviceToken::query()
                ->where('token', $token)
                ->update(['last_used_at' => now()]);
        } catch (Throwable $exception) {
            Log::warning('fcm_send_exception', [
                'error' => $exception->getMessage(),
                'token_fp' => $this->tokenFingerprint($token),
            ]);
        }
    }

    public function isConfigured(): bool
    {
        return filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.credentials'));
    }

    /**
     * @return 'invalidate_token'|'preserve_retry'|'preserve_config'|'preserve_app_error'
     */
    public function classifyFailure(int $status, ?string $errorCode): string
    {
        $code = strtoupper((string) $errorCode);

        if (in_array($code, self::PERMANENTLY_INVALID_ERROR_CODES, true)) {
            return 'invalidate_token';
        }

        if (in_array($status, [401, 403], true) || in_array($code, ['UNAUTHENTICATED', 'PERMISSION_DENIED'], true)) {
            return 'preserve_config';
        }

        if (in_array($status, [429, 500, 503, 504], true) || in_array($code, ['QUOTA_EXCEEDED', 'UNAVAILABLE', 'INTERNAL', 'RESOURCE_EXHAUSTED'], true)) {
            return 'preserve_retry';
        }

        // Generic 404 without structured UNREGISTERED still usually means missing registration.
        if ($status === 404) {
            return 'invalidate_token';
        }

        // Other 4xx (including ambiguous 400 without INVALID_ARGUMENT) — do not delete tokens.
        if ($status >= 400 && $status < 500) {
            return 'preserve_app_error';
        }

        return 'preserve_retry';
    }

    public function extractErrorCode(Response $response): ?string
    {
        $json = $response->json();
        if (! is_array($json)) {
            return null;
        }

        $details = $json['error']['details'] ?? null;
        if (is_array($details)) {
            foreach ($details as $detail) {
                if (! is_array($detail)) {
                    continue;
                }
                if (isset($detail['errorCode']) && is_string($detail['errorCode']) && $detail['errorCode'] !== '') {
                    return $detail['errorCode'];
                }
            }
        }

        $status = $json['error']['status'] ?? null;

        return is_string($status) && $status !== '' ? $status : null;
    }

    /**
     * Delete only the exact token string, and only if it was not refreshed after send started.
     */
    public function invalidateTokenIfUnchanged(string $token, CarbonInterface $capturedAt): void
    {
        DeviceToken::query()
            ->where('token', $token)
            ->where(function ($query) use ($capturedAt): void {
                $query->whereNull('updated_at')
                    ->orWhere('updated_at', '<=', $capturedAt);
            })
            ->delete();
    }

    public function tokenFingerprint(string $token): string
    {
        return substr(hash('sha256', $token), 0, 12);
    }

    private function accessToken(): ?string
    {
        $path = (string) config('services.firebase.credentials');
        if ($path === '' || ! is_readable($path)) {
            return null;
        }

        try {
            $json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($json)) {
                return null;
            }

            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claim = $this->base64Url(json_encode([
                'iss' => $json['client_email'] ?? '',
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));

            $unsigned = $header.'.'.$claim;
            $key = openssl_pkey_get_private((string) ($json['private_key'] ?? ''));
            if ($key === false) {
                return null;
            }

            $signature = '';
            if (! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $jwt = $unsigned.'.'.$this->base64Url($signature);

            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($tokenResponse->failed()) {
                Log::warning('fcm_oauth_failed', ['body' => $tokenResponse->body()]);

                return null;
            }

            return $tokenResponse->json('access_token');
        } catch (Throwable $exception) {
            Log::warning('fcm_oauth_exception', ['error' => $exception->getMessage()]);

            return null;
        }
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
