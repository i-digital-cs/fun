<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\MarketingAccountStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingAccount extends Model
{
    protected $table = 'marketing_accounts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'platform_id',
        'external_account_id',
        'name',
        'currency',
        'timezone',
        'status',
        'last_synced_at',
        'meta_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MarketingAccountStatus::class,
            'last_synced_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketingPlatform::class, 'platform_id');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(MarketingIntegrationCredential::class, 'marketing_account_id');
    }
}
