<?php

namespace App\Services\Safety;

use App\Enums\ReportableType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\Report;
use App\Models\User;
use App\Support\ChatMessageSanitizer;

class CreateReportService
{
    public function create(
        User $reporter,
        ReportableType $type,
        int $reportableId,
        ReportReason $reason,
        ?string $description = null,
    ): Report {
        if (! $reporter->canParticipate()) {
            throw new DomainException(__('safety.not_allowed'));
        }

        $target = $this->resolveTarget($type, $reportableId);

        if ($target === null) {
            throw new DomainException(__('safety.target_not_found'));
        }

        if ($type === ReportableType::User && $target instanceof User) {
            if ($target->id === $reporter->id) {
                throw new DomainException(__('safety.cannot_report_self'));
            }
        }

        if ($type === ReportableType::Message && $target instanceof EventMessage) {
            if ($target->user_id === $reporter->id) {
                throw new DomainException(__('safety.cannot_report_own_message'));
            }

            if (! $target->event->isMember($reporter)) {
                throw new DomainException(__('safety.members_only_report_message'));
            }
        }

        $cleanDescription = $description !== null
            ? ChatMessageSanitizer::clean($description)
            : null;

        if ($cleanDescription === '') {
            $cleanDescription = null;
        }

        if ($cleanDescription !== null && mb_strlen($cleanDescription) > 1000) {
            throw new DomainException(__('safety.description_too_long'));
        }

        $duplicate = Report::query()
            ->where('reporter_id', $reporter->id)
            ->where('reportable_type', $type->value)
            ->where('reportable_id', $reportableId)
            ->where('status', ReportStatus::Pending)
            ->exists();

        if ($duplicate) {
            throw new DomainException(__('safety.report_already_pending'));
        }

        return Report::query()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $type,
            'reportable_id' => $reportableId,
            'reason' => $reason,
            'description' => $cleanDescription,
            'status' => ReportStatus::Pending,
        ]);
    }

    private function resolveTarget(ReportableType $type, int $id): Event|EventMessage|User|null
    {
        return match ($type) {
            ReportableType::User => User::query()->find($id),
            ReportableType::Event => Event::query()->find($id),
            ReportableType::Message => EventMessage::query()->with('event')->find($id),
        };
    }
}
