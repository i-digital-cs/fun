<?php

namespace App\Exceptions;

class OtpCooldownException extends DomainException
{
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds,
        public readonly string $resendAvailableAt,
    ) {
        parent::__construct($message);
    }
}
