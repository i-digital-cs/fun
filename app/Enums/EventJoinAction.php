<?php

namespace App\Enums;

enum EventJoinAction: string
{
    case RequestToJoin = 'request_to_join';
    case RequestPending = 'request_pending';
    case Owned = 'owned';
    case Joined = 'joined';
    case RequestRejected = 'request_rejected';
    case EventFull = 'event_full';
    case SexMismatch = 'sex_mismatch';
    case NotAvailable = 'not_available';
}
