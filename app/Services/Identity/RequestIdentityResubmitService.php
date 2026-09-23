<?php

namespace App\Services\Identity;

use App\Enums\AccountStatus;
use App\Enums\PlatformRole;
use App\Exceptions\DomainException;
use App\Models\User;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Support\Facades\DB;

class RequestIdentityResubmitService
{
    public function __construct(
        private readonly AdminAuditLogger $audit,
    ) {}

    public function request(User $actor, User $target): User
    {
        if (! $actor->platform_role instanceof PlatformRole) {
            throw new DomainException(__('admin.unauthorized'));
        }

        if ($target->isDeactivated()) {
            throw new DomainException(__('identity.cannot_request_while_deactivated'));
        }

        if ($target->needsIdentityDocuments()) {
            throw new DomainException(__('identity.already_pending_resubmit'));
        }

        if (! $target->hasRequiredIdentityDocuments()) {
            throw new DomainException(__('identity.no_documents_to_resubmit'));
        }

        return DB::transaction(function () use ($actor, $target) {
            $target->forceFill([
                'status' => AccountStatus::PendingDocuments,
            ])->save();

            $this->audit->log($actor, 'identity.resubmit_requested', User::class, $target->id);

            return $target->refresh();
        });
    }
}
