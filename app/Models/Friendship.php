<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => FriendshipStatus::class,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    public function involves(int $userId): bool
    {
        return (int) $this->requester_id === $userId || (int) $this->addressee_id === $userId;
    }

    public function otherUserId(int $userId): int
    {
        return (int) $this->requester_id === $userId
            ? (int) $this->addressee_id
            : (int) $this->requester_id;
    }
}
