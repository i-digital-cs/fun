<?php

namespace App\Repositories\Marketing;

use App\Enums\Marketing\MarketingCredentialType;
use App\Models\Marketing\MarketingIntegrationCredential;
use App\Models\User;

class MarketingCredentialRepository
{
    /**
     * Persist an encrypted credential payload. Callers must never log $payload.
     *
     * @param  array<string, mixed>  $payload  Plaintext structure encrypted at rest
     * @param  list<string>|null  $scopes
     */
    public function storeEncrypted(
        int $platformId,
        MarketingCredentialType $type,
        array $payload,
        ?User $actor = null,
        ?int $marketingAccountId = null,
        ?array $scopes = null,
        ?\DateTimeInterface $expiresAt = null,
    ): MarketingIntegrationCredential {
        return MarketingIntegrationCredential::query()->create([
            'platform_id' => $platformId,
            'marketing_account_id' => $marketingAccountId,
            'credential_type' => $type,
            'encrypted_payload' => $payload,
            'scopes_json' => $scopes,
            'expires_at' => $expiresAt,
            'rotated_at' => now(),
            'created_by' => $actor?->id,
        ]);
    }

    public function find(int $id): ?MarketingIntegrationCredential
    {
        return MarketingIntegrationCredential::query()->find($id);
    }
}
