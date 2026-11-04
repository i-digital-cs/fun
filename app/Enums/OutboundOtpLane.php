<?php

namespace App\Enums;

/**
 * Partition outbound OTP volume so registration/password-reset floods
 * cannot exhaust the budget used by authenticated member resends.
 */
enum OutboundOtpLane: string
{
    /** Registration, login auto-send, forgot-password — abuse-prone. */
    case AbuseProne = 'abuse';

    /** Authenticated email-verify resend / profile email change. */
    case Member = 'member';
}
