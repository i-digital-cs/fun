<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingPlatform extends Model
{
    protected $table = 'marketing_platforms';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_enabled',
        'capabilities_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'capabilities_json' => 'array',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(MarketingAccount::class, 'platform_id');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(MarketingIntegrationCredential::class, 'platform_id');
    }
}
