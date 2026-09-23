<?php

namespace App\Models;

use App\Enums\EventMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventMember extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'user_id',
        'role',
        'ticket_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => EventMemberRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EventMember $member): void {
            if (! filled($member->ticket_code)) {
                $member->ticket_code = static::generateUniqueTicketCode();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateUniqueTicketCode(): string
    {
        do {
            // 10 chars, unambiguous alphabet — globally unique per membership
            $code = strtoupper(Str::random(10));
            $code = preg_replace('/[01OI]/', 'X', $code) ?: $code;
        } while (static::query()->where('ticket_code', $code)->exists());

        return $code;
    }

    public function ensureTicketCode(): string
    {
        if (! filled($this->ticket_code)) {
            $this->forceFill([
                'ticket_code' => static::generateUniqueTicketCode(),
            ])->save();
        }

        return (string) $this->ticket_code;
    }
}
