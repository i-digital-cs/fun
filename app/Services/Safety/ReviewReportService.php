<?php

namespace App\Services\Safety;

use App\Enums\ReportStatus;
use App\Exceptions\DomainException;
use App\Models\Report;
use App\Models\User;
use App\Services\Admin\AdminAuditLogger;
use App\Support\ChatMessageSanitizer;

class ReviewReportService
{
    public function __construct(private readonly AdminAuditLogger $audit)
    {
    }

    public function review(
        User $moderator,
        Report $report,
        ReportStatus $status,
        ?string $moderationAction = null,
    ): Report {
        if ($moderator->platform_role?->canReviewReports() !== true) {
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

        $this->audit->log($moderator, 'report.reviewed', Report::class, $report->id, [
            'status' => $status->value,
            'moderation_action' => $action,
        ]);

        return $report->refresh();
    }
}
