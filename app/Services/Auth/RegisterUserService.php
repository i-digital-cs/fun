<?php

namespace App\Services\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserService
{
    /**
     * @param  array{name: string, email: string, password: string, locale?: string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::query()->create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'status' => AccountStatus::PendingDocuments,
                'locale' => $data['locale'] ?? app()->getLocale(),
            ]);
        });
    }
}
