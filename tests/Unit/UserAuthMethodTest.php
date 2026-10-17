<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAuthMethodTest extends TestCase
{
    #[Test]
    public function it_detects_google_only_accounts(): void
    {
        $user = $this->userWithAuth(googleId: 'google-sub-1', passwordHash: null);

        $this->assertTrue($user->hasGoogleLogin());
        $this->assertFalse($user->hasPasswordLogin());
        $this->assertSame('google', $user->authMethod());
    }

    #[Test]
    public function it_detects_password_only_accounts(): void
    {
        $user = $this->userWithAuth(googleId: null, passwordHash: '$2y$10$abcdefghijklmnopqrstuv');

        $this->assertFalse($user->hasGoogleLogin());
        $this->assertTrue($user->hasPasswordLogin());
        $this->assertSame('password', $user->authMethod());
    }

    #[Test]
    public function it_detects_linked_google_and_password_accounts(): void
    {
        $user = $this->userWithAuth(googleId: 'google-sub-2', passwordHash: '$2y$10$abcdefghijklmnopqrstuv');

        $this->assertTrue($user->hasGoogleLogin());
        $this->assertTrue($user->hasPasswordLogin());
        $this->assertSame('google+password', $user->authMethod());
    }

    private function userWithAuth(?string $googleId, ?string $passwordHash): User
    {
        $user = new User;
        $user->setRawAttributes([
            'google_id' => $googleId,
            'password' => $passwordHash,
        ], true);

        return $user;
    }
}
