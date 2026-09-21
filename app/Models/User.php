<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentType;
use App\Enums\PlatformRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'platform_role',
        'google_id',
        'locale',
        'city_id',
        'distance_km',
        'profile_image_path',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => AccountStatus::class,
            'platform_role' => PlatformRole::class,
            'deactivated_at' => 'datetime',
            'distance_km' => 'integer',
        ];
    }

    public function identityDocuments(): HasMany
    {
        return $this->hasMany(IdentityDocument::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    public function reportsFiled(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    public function isDeactivated(): bool
    {
        return $this->status === AccountStatus::Deactivated;
    }

    public function needsIdentityDocuments(): bool
    {
        return $this->status === AccountStatus::PendingDocuments;
    }

    public function canParticipate(): bool
    {
        return $this->status->canParticipate();
    }

    public function isPlatformAdmin(): bool
    {
        return $this->platform_role instanceof PlatformRole;
    }

    public function hasRequiredIdentityDocuments(): bool
    {
        $types = $this->identityDocuments()->pluck('type')->map(
            fn ($type) => $type instanceof IdentityDocumentType ? $type->value : $type
        )->all();

        foreach (IdentityDocumentType::required() as $required) {
            if (! in_array($required->value, $types, true)) {
                return false;
            }
        }

        return true;
    }
}
