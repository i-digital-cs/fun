<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    public const TYPE_LIKE = 'like';

    public const TYPE_DISLIKE = 'dislike';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'type',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(EventMessage::class, 'message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
