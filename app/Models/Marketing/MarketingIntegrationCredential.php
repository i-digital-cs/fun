<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\MarketingCredentialType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingIntegrationCredential extends Model
{
    protected $table = 'marketing_integration_credentials';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'marketing_account_id',
        'platform_id',
        'credential_type',
        'encrypted_payload',
        'scopes_json',
        'expires_at',
        'rotated_at',
        'created_by',
    ];

    /**
     * Never expose encrypted payloads via arrays/JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'encrypted_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credential_type' => MarketingCredentialType::class,
            'encrypted_payload' => 'encrypted:array',
            'scopes_json' => 'array',
            'expires_at' => 'datetime',
            'rotated_at' => 'datetime',
        ];
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketingPlatform::class, 'platform_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketingAccount::class, 'marketing_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
