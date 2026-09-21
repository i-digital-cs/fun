<?php

namespace App\Enums;

enum AccountStatus: string
{
    case PendingDocuments = 'pending_documents';
    case Active = 'active';
    case Deactivated = 'deactivated';

    public function canParticipate(): bool
    {
        return $this === self::Active;
    }

    public function canUploadIdentityDocuments(): bool
    {
        return $this === self::PendingDocuments;
    }
}
