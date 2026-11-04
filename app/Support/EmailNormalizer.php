<?php

namespace App\Support;

final class EmailNormalizer
{
    /**
     * Normalize emails for rate-limit keys and uniqueness checks.
     * Lowercases the full address and trims whitespace. Does not apply
     * provider-specific Gmail dot-stripping (would over-collapse distinct mailboxes).
     */
    public static function normalize(?string $email): string
    {
        $email = strtolower(trim((string) $email));

        return $email;
    }
}
