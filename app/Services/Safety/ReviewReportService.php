<?php

namespace App\Services\Safety;

use App\Enums\ReportStatus;
use App\Exceptions\DomainException;
use App\Models\Report;
use App\Models\User;
use App\Support\ChatMessageSanitizer;

class ReviewReportService
{
    public function review(
        User $moderator,
        Report $report,
        ReportStatus $status,
        ?string $moderationAction = null,
    ): Report {
        if (! $moderator->isPlatformAdmin()) {
            throw new DomainException(__('safety.unauthorized'));
        }

        if ($status === ReportStatus::Pending) {
            throw new DomainException(__('safety.invalid_status'));
        }

        $action = $moderationAction !== null
            ? ChatMessageSanitizer::clean($moderationAction)
            : null;

        if ($action === '') {
            $action = null;
        }

        $report->forceFill([
            'status' => $status,
            'moderation_action' => $action,
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
        ])->save();

        return $report->refresh();
    }
}
