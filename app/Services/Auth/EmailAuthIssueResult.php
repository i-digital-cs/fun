<?php

namespace App\Services\Auth;

use Carbon\CarbonInterface;

final class EmailAuthIssueResult
{
    public function __construct(
        public readonly bool $issued,
        public readonly int $retryAfterSeconds,
        public readonly CarbonInterface $resendAvailableAt,
        public readonly int $expiresInMinutes,
        public readonly bool $deliveryFailed = false,
    ) {
    }

    /**
     * @return array{retry_after_seconds: int, resend_available_at: string, expires_in_minutes: int, issued: bool}
     */
    public function toArray(): array
    {
        return [
            'issued' => $this->issued,
            'retry_after_seconds' => $this->retryAfterSeconds,
            'resend_available_at' => $this->resendAvailableAt->toIso8601String(),
            'expires_in_minutes' => $this->expiresInMinutes,
        ];
    }
}
