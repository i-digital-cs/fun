<?php

namespace App\Exceptions;

/**
 * Registration refused before user creation (budget, kill-switch, captcha).
 * Controllers should respond with a generic message (no enumeration).
 */
class RegistrationBlockedException extends DomainException
{
    public function __construct(
        string $message = '',
        public readonly int $status = 429,
        public readonly string $reasonCode = 'blocked',
    ) {
        parent::__construct($message !== '' ? $message : __('auth.registration_unavailable'));
    }
}
