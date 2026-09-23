<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'display_name',
        'logo_path',
        'logo_disk',
        'android_latest_version',
        'android_min_version',
        'ios_latest_version',
        'ios_min_version',
        'force_update',
        'android_store_url',
        'ios_store_url',
        'update_message_en',
        'update_message_ar',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'force_update' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
