<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectConversation extends Model
{
    protected $fillable = [
        'user_low_id',
        'user_high_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'conversation_id');
    }

    public function userLow(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_low_id');
    }

    public function userHigh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_high_id');
    }

    public function involves(int $userId): bool
    {
        return (int) $this->user_low_id === $userId || (int) $this->user_high_id === $userId;
    }

    public function otherUserId(int $userId): int
    {
        return (int) $this->user_low_id === $userId
            ? (int) $this->user_high_id
            : (int) $this->user_low_id;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function orderedPair(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }
}
