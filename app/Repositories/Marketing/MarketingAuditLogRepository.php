<?php

namespace App\Repositories\Marketing;

use App\Models\Marketing\MarketingAuditLog;

class MarketingAuditLogRepository
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function create(array $attributes): MarketingAuditLog
    {
        return MarketingAuditLog::query()->create([
            'actor_id' => $attributes['actor_id'] ?? null,
            'action' => $attributes['action'],
            'subject_type' => $attributes['subject_type'] ?? null,
            'subject_id' => $attributes['subject_id'] ?? null,
            'ip' => $attributes['ip'] ?? null,
            'correlation_id' => $attributes['correlation_id'] ?? null,
            'meta_json' => $attributes['meta_json'] ?? null,
            'created_at' => $attributes['created_at'] ?? now(),
        ]);
    }
}
