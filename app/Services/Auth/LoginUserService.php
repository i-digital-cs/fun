<?php

namespace App\Services\Auth;

use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginUserService
{
    /**
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     */
    public function attempt(array $credentials): User
    {
        $user = $this->authenticate($credentials);

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        session()->regenerate();

        return $user;
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function authenticate(array $credentials): User
    {
        $user = User::query()->where('email', strtolower($credentials['email']))->first();

        if (! $user || $user->password === null || ! Hash::check($credentials['password'], $user->password)) {
            throw new DomainException(__('auth.failed'));
        }

        return $user;
    }

    public function issueApiToken(User $user, string $deviceName = 'api'): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }
}
