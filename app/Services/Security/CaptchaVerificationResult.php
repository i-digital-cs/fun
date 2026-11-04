<?php

namespace App\Services\Security;

final class CaptchaVerificationResult
{
    /**
     * @param  list<string>  $errorCodes
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $reason = '',
        public readonly array $errorCodes = [],
        public readonly ?string $hostname = null,
        public readonly ?string $action = null,
    ) {
    }

    public static function success(?string $hostname = null, ?string $action = null): self
    {
        return new self(ok: true, hostname: $hostname, action: $action);
    }

    /**
     * @param  list<string>  $errorCodes
     */
    public static function failure(string $reason, array $errorCodes = []): self
    {
        return new self(ok: false, reason: $reason, errorCodes: $errorCodes);
    }
}
