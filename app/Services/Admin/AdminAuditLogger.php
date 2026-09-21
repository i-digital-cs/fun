<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\User;

class AdminAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function log(
        User $actor,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $meta = null,
    ): AdminAuditLog {
        return AdminAuditLog::query()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta,
        ]);
    }
}
