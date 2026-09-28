<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentType;
use App\Enums\PlatformRole;
use App\Enums\UserSex;
use App\Support\UserPublicId;
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
        'public_id',
        'name',
        'email',
        'password',
        'google_id',
        'apple_id',
        'locale',
        'sex',
        'city_id',
        'distance_km',
        'profile_image_path',
        'notify_friend_events',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'apple_id',
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
            'sex' => UserSex::class,
            'deactivated_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'distance_km' => 'integer',
            'notify_friend_events' => 'boolean',
        ];
    }

    public function needsSex(): bool
    {
        return $this->sex === null;
    }

    public function hasGoogleLogin(): bool
    {
        return filled($this->google_id);
    }

    public function hasAppleLogin(): bool
    {
        return filled($this->apple_id);
    }

    public function hasPasswordLogin(): bool
    {
        return $this->password !== null && $this->password !== '';
    }

    public function hasPendingDeletion(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    /**
     * Admin label for how this account can sign in: google | apple | password | combinations.
     */
    public function authMethod(): string
    {
        $google = $this->hasGoogleLogin();
        $apple = $this->hasAppleLogin();
        $password = $this->hasPasswordLogin();
        $parts = [];
        if ($google) {
            $parts[] = 'google';
        }
        if ($apple) {
            $parts[] = 'apple';
        }
        if ($password) {
            $parts[] = 'password';
        }

        return $parts === [] ? 'password' : implode('+', $parts);
    }

    public function authMethodLabel(): string
    {
        return match ($this->authMethod()) {
            'google' => __('admin.auth_method_google'),
            'apple' => __('admin.auth_method_apple'),
            'google+apple' => __('admin.auth_method_social'),
            'google+password', 'apple+password', 'google+apple+password', 'both' => __('admin.auth_method_both'),
            default => __('admin.auth_method_password'),
        };
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! filled($user->public_id)) {
                $user->public_id = UserPublicId::generate();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        // Keep numeric {user} route model binding for block/admin.
        return 'id';
    }

    public function publicProfileUrl(): ?string
    {
        if (! filled($this->public_id)) {
            return null;
        }

        return route('users.public.show', $this->public_id);
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

    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function eventMemberships(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function eventFavorites(): HasMany
    {
        return $this->hasMany(EventFavorite::class);
    }

    public function friendshipsRequested(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function friendshipsReceived(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
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

    public function canUploadIdentityDocuments(): bool
    {
        return $this->status->canUploadIdentityDocuments()
            || ($this->isActive() && ! $this->hasRequiredIdentityDocuments());
    }

    /**
     * Pending again after admin asked for new documents (previous files may still exist).
     */
    public function identityResubmitRequired(): bool
    {
        return $this->needsIdentityDocuments() && $this->identityDocuments()->exists();
    }

    public function canParticipate(): bool
    {
        return $this->status->canParticipate();
    }

    public function isPlatformAdmin(): bool
    {
        return $this->platform_role instanceof PlatformRole;
    }

    public function isSuperAdmin(): bool
    {
        return $this->platform_role === PlatformRole::SuperAdmin;
    }

    /**
     * Default route after login / identity completion.
     */
    public function defaultHomeRoute(): string
    {
        return $this->isPlatformAdmin() ? 'admin.dashboard' : 'dashboard';
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
