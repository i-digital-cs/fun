<?php

namespace App\Models;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\UserSex;
use App\Support\MediaDisk;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'slug',
        'description',
        'venue',
        'latitude',
        'longitude',
        'maps_url',
        'starts_at',
        'ends_at',
        'max_participants',
        'sex',
        'requirements',
        'expected_cost',
        'cost_per_person',
        'image_path',
        'image_disk',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            if (blank($event->slug)) {
                $event->slug = static::uniqueSlug((string) $event->title);
            }
        });

        static::created(function (Event $event): void {
            // Re-resolve with id available so collisions append -{id}.
            $slug = static::uniqueSlug((string) $event->title, (int) $event->id);
            if ($slug !== $event->slug) {
                $event->forceFill(['slug' => $slug])->saveQuietly();
            }
        });

        static::updating(function (Event $event): void {
            if ($event->isDirty('title') || blank($event->slug)) {
                $event->slug = static::uniqueSlug((string) $event->title, (int) $event->id);
            }
        });
    }

    /**
     * Route parameters for the public event show URL (`/events/{id}/{slug}`).
     *
     * @return array{event: int, slug: string}
     */
    public function showRoute(): array
    {
        return [
            'event' => (int) $this->getKey(),
            'slug' => $this->slug ?: static::uniqueSlug((string) $this->title, (int) $this->getKey()),
        ];
    }

    public function showUrl(): string
    {
        return route('events.show', $this->showRoute());
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'event';
        }
        $base = Str::limit($base, 140, '');

        $slug = $base;
        $query = static::query()->where('slug', $slug);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if (! $query->exists()) {
            return $slug;
        }

        if ($ignoreId !== null) {
            $withId = Str::limit($base, 130, '').'-'.$ignoreId;
            $exists = static::query()
                ->where('slug', $withId)
                ->whereKeyNot($ignoreId)
                ->exists();
            if (! $exists) {
                return $withId;
            }
        }

        for ($i = 2; $i < 1000; $i++) {
            $candidate = Str::limit($base, 130, '').'-'.$i;
            $q = static::query()->where('slug', $candidate);
            if ($ignoreId !== null) {
                $q->whereKeyNot($ignoreId);
            }
            if (! $q->exists()) {
                return $candidate;
            }
        }

        return $base.'-'.Str::lower(Str::random(6));
    }

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
            'latitude' => 'float',
            'longitude' => 'float',
            'status' => EventStatus::class,
            'sex' => UserSex::class,
        ];
    }

    /**
     * Whether this event’s audience allows the given user (mix = anyone).
     */
    public function allowsUserSex(?User $user): bool
    {
        $audience = $this->sex ?? UserSex::Mix;

        if ($audience === UserSex::Mix) {
            return true;
        }

        if ($user === null || $user->sex === null) {
            return false;
        }

        return $user->sex === $audience;
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

    public function favorites(): HasMany
    {
        return $this->hasMany(EventFavorite::class);
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

    public function imageUrl(?string $size = 'detail'): ?string
    {
        if ($this->image_path === null) {
            return null;
        }

        $disk = $this->image_disk ?: MediaDisk::public();
        $path = $this->image_path;

        if (in_array($size, ['card', 'thumb'], true)) {
            $cardPath = event_image_card_path($path);
            if ($cardPath !== null && Storage::disk($disk)->exists($cardPath)) {
                $path = $cardPath;
            }
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Uploaded cover or category-matched stock activity image.
     *
     * @param  'card'|'detail'|'thumb'  $size
     */
    public function coverUrl(string $size = 'card'): string
    {
        $category = $this->relationLoaded('category') ? $this->category : $this->category()->first();
        $hint = trim($this->title.' '.($category?->name_en ?? '').' '.($category?->name_ar ?? ''));

        return event_cover_url(
            $this->imageUrl($size === 'detail' ? 'detail' : 'card'),
            (int) $this->id,
            $category?->slug,
            $hint,
            $size,
        );
    }

    /**
     * @param  'card'|'detail'|'thumb'  $size
     * @return array{src: string, srcset: ?string, sizes: string, width: int, height: int, webp_srcset: ?string}
     */
    public function coverAttrs(string $size = 'card'): array
    {
        $category = $this->relationLoaded('category') ? $this->category : $this->category()->first();
        $hint = trim($this->title.' '.($category?->name_en ?? '').' '.($category?->name_ar ?? ''));

        return event_cover_attrs(
            $this->imageUrl('detail'),
            $this->imageUrl('card'),
            (int) $this->id,
            $category?->slug,
            $hint,
            $size,
        );
    }

    public function hasPlacePin(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Human-facing place line: venue · city (skips placeholder venue text).
     */
    public function locationLabel(): string
    {
        $city = $this->relationLoaded('city') ? $this->city : $this->city()->first();
        $cityName = $city?->localizedName() ?? '';

        if ($this->hasMeaningfulVenue()) {
            $venue = trim((string) $this->venue);
            // Reverse-geocode / user strings that already include an area stay as-is.
            if (str_contains($venue, '·') || str_contains($venue, ',')) {
                return $venue;
            }
            if ($cityName !== '' && ! str_contains(mb_strtolower($venue), mb_strtolower($cityName))) {
                return $venue.' · '.$cityName;
            }

            return $venue;
        }

        if ($cityName !== '') {
            return $cityName;
        }

        if ($this->hasPlacePin()) {
            return sprintf('%.5f, %.5f', (float) $this->latitude, (float) $this->longitude);
        }

        return '';
    }

    public function hasMeaningfulVenue(): bool
    {
        $venue = trim((string) ($this->venue ?? ''));
        if ($venue === '') {
            return false;
        }

        $normalized = mb_strtolower($venue);
        $placeholders = [
            'venue',
            'location',
            'place',
            'المكان',
            'موقع',
            'title',
            'العنوان',
        ];

        return ! in_array($normalized, $placeholders, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function locationPayload(): array
    {
        $city = $this->relationLoaded('city') ? $this->city : null;

        return [
            'label' => $this->locationLabel() ?: null,
            'venue' => $this->hasMeaningfulVenue() ? trim((string) $this->venue) : null,
            'venue_raw' => $this->venue,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'maps_url' => $this->maps_url,
            'maps_open_url' => $this->mapsOpenUrl(),
            'has_place_pin' => $this->hasPlacePin(),
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->localizedName(),
                'name_en' => $city->name_en,
                'name_ar' => $city->name_ar,
                'latitude' => $city->latitude !== null ? (float) $city->latitude : null,
                'longitude' => $city->longitude !== null ? (float) $city->longitude : null,
            ] : null,
        ];
    }

    public function mapsOpenUrl(): ?string
    {
        // Pin coords are the source of truth — never open a stale pasted link
        // that no longer matches the map marker.
        if ($this->hasPlacePin()) {
            return app(\App\Support\MapsUrlParser::class)
                ->googleMapsOpenUrl((float) $this->latitude, (float) $this->longitude);
        }

        return filled($this->maps_url) ? $this->maps_url : null;
    }
}
