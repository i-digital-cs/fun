<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmPushService
{
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

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $payload['title'],
                            'body' => $payload['body'],
                        ],
                        'data' => array_map('strval', $payload['data'] ?? []),
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('fcm_send_failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                // Drop clearly invalid tokens.
                if (in_array($response->status(), [400, 404], true)) {
                    DeviceToken::query()->where('token', $token)->delete();
                }
            } else {
                DeviceToken::query()
                    ->where('token', $token)
                    ->update(['last_used_at' => now()]);
            }
        } catch (Throwable $exception) {
            Log::warning('fcm_send_exception', ['error' => $exception->getMessage()]);
        }
    }

    public function isConfigured(): bool
    {
        return filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.credentials'));
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
