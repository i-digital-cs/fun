<?php

namespace App\Contracts\Security;

use App\Services\Security\CaptchaVerificationResult;

interface CaptchaVerifier
{
    /**
     * Verify a client-provided challenge token for the given action.
     */
    public function verify(?string $token, string $action, ?string $remoteIp = null): CaptchaVerificationResult;
}
