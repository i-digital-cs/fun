<?php

namespace App\Services\Auth;

use App\Exceptions\DomainException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class AppleIdTokenVerifier
{
    /**
     * @return array{sub: string, email: ?string, email_verified: bool}
     *
     * @throws DomainException
     */
    public function verify(string $idToken): array
    {
        $audiences = config('services.apple.client_ids', []);

        if ($audiences === []) {
            throw new DomainException(__('auth.apple_not_configured'));
        }

        try {
            $keys = $this->appleJwks();
            $payload = JWT::decode($idToken, JWK::parseKeySet($keys));
        } catch (Throwable $e) {
            throw new DomainException(__('auth.apple_invalid_token'));
        }

        $data = (array) $payload;
        $iss = (string) ($data['iss'] ?? '');
        $aud = $data['aud'] ?? null;
        $sub = (string) ($data['sub'] ?? '');

        if ($iss !== 'https://appleid.apple.com' || $sub === '') {
            throw new DomainException(__('auth.apple_invalid_token'));
        }

        $audList = is_array($aud) ? $aud : [$aud];
        $audOk = false;
        foreach ($audList as $value) {
            if (in_array((string) $value, $audiences, true)) {
                $audOk = true;
                break;
            }
        }

        if (! $audOk) {
            throw new DomainException(__('auth.apple_invalid_token'));
        }

        $email = isset($data['email']) ? strtolower((string) $data['email']) : null;
        if ($email === '') {
            $email = null;
        }

        $emailVerified = ($data['email_verified'] ?? false) === true
            || ($data['email_verified'] ?? '') === 'true'
            || ($data['email_verified'] ?? '') === 'True';

        return [
            'sub' => $sub,
            'email' => $email,
            'email_verified' => $emailVerified || $email !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appleJwks(): array
    {
        return Cache::remember('apple.auth.jwks', 3600, function (): array {
            $response = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');

            if (! $response->successful()) {
                throw new DomainException(__('auth.apple_invalid_token'));
            }

            $json = $response->json();
            if (! is_array($json) || ! isset($json['keys'])) {
                throw new DomainException(__('auth.apple_invalid_token'));
            }

            return $json;
        });
    }
}
