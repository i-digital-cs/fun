<?php

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super_admin';
    case Moderator = 'moderator';

    public function canModerateUsers(): bool
    {
        return true;
    }

    public function canManageCatalog(): bool
    {
        return true;
    }

    public function canModerateEvents(): bool
    {
        return true;
    }

    public function canReviewReports(): bool
    {
        return true;
    }

    public function canViewIdentityDocuments(): bool
    {
        return true;
    }

    public function canModerateChat(): bool
    {
        return true;
    }

    public function canManageSiteSettings(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canViewAuditLogs(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * Marketing Manager — Phase 1: SuperAdmin only.
     * Capability methods are separated for future finer RBAC without a new package.
     */
    public function canViewMarketing(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canManageMarketing(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canConnectAdAccounts(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canApproveMarketingActions(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canExecuteMarketingActions(): bool
    {
        return $this === self::SuperAdmin;
    }
}
