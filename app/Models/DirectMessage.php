<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'body',
        'client_uuid',
        'reply_to_id',
        'message_type',
        'media_path',
        'media_mime',
        'media_bytes',
        'media_duration_ms',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DirectConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }
}
