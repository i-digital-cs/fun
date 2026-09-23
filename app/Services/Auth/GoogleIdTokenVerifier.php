<?php

namespace App\Services\Auth;

use App\Exceptions\DomainException;
use Google_Client;

class GoogleIdTokenVerifier
{
    /**
     * @return array{
     *     sub: string,
     *     email: string,
     *     email_verified: bool,
     *     name: ?string,
     *     picture: ?string
     * }
     *
     * @throws DomainException
     */
    public function verify(string $idToken): array
    {
        $audiences = config('services.google.client_ids', []);

        if ($audiences === []) {
            throw new DomainException(__('auth.google_not_configured'));
        }

        $payload = false;

        foreach ($audiences as $audience) {
            $client = new Google_Client(['client_id' => $audience]);
            $payload = $client->verifyIdToken($idToken);

            if ($payload !== false) {
                break;
            }
        }

        if ($payload === false) {
            throw new DomainException(__('auth.google_invalid_token'));
        }

        $email = strtolower((string) ($payload['email'] ?? ''));
        $sub = (string) ($payload['sub'] ?? '');

        if ($email === '' || $sub === '') {
            throw new DomainException(__('auth.google_invalid_token'));
        }

        $emailVerified = ($payload['email_verified'] ?? false) === true
            || ($payload['email_verified'] ?? '') === 'true';

        if (! $emailVerified) {
            throw new DomainException(__('auth.google_email_unverified'));
        }

        return [
            'sub' => $sub,
            'email' => $email,
            'email_verified' => true,
            'name' => isset($payload['name']) ? (string) $payload['name'] : null,
            'picture' => isset($payload['picture']) ? (string) $payload['picture'] : null,
        ];
    }
}
