<?php

namespace App\Models;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Support\MediaDisk;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'city_id',
        'title',
        'description',
        'venue',
        'starts_at',
        'ends_at',
        'max_participants',
        'requirements',
        'expected_cost',
        'cost_per_person',
        'image_path',
        'image_disk',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_participants' => 'integer',
            'cost_per_person' => 'decimal:2',
            'status' => EventStatus::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(EventJoinRequest::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EventMessage::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(EventRating::class);
    }

    public function isReadyForRatings(): bool
    {
        if ($this->status === EventStatus::Cancelled) {
            return false;
        }

        if ($this->status === EventStatus::Completed) {
            return true;
        }

        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function isMember(User $user): bool
    {
        if ($this->relationLoaded('members')) {
            return $this->members->contains('user_id', $user->id);
        }

        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->memberRoleFor($user) === EventMemberRole::Owner;
    }

    public function acceptsJoinRequests(): bool
    {
        return $this->status === EventStatus::Published && ! $this->isFull();
    }

    public function acceptedMembersCount(): int
    {
        if (array_key_exists('members_count', $this->attributes)) {
            return (int) $this->attributes['members_count'];
        }

        if ($this->relationLoaded('members')) {
            return $this->members->count();
        }

        return $this->members()->count();
    }

    public function isFull(): bool
    {
        return $this->acceptedMembersCount() >= $this->max_participants;
    }

    public function remainingCapacity(): int
    {
        return max(0, $this->max_participants - $this->acceptedMembersCount());
    }

    public function memberRoleFor(User $user): ?EventMemberRole
    {
        if ($this->relationLoaded('members')) {
            $member = $this->members->firstWhere('user_id', $user->id);

            return $member?->role;
        }

        $member = $this->members()->where('user_id', $user->id)->first();

        return $member?->role;
    }

    public function isManagedBy(User $user): bool
    {
        $role = $this->memberRoleFor($user);

        return $role?->canManageEvent() === true;
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path === null) {
            return null;
        }

        $disk = $this->image_disk ?: MediaDisk::public();

        return Storage::disk($disk)->url($this->image_path);
    }
}
