<?php

namespace App\Services\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserService
{
    /**
     * @param  array{name: string, email: string, password: string, locale?: string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'locale' => $data['locale'] ?? app()->getLocale(),
            ]);
            $user->forceFill([
                'status' => AccountStatus::PendingDocuments,
            ])->save();

            return $user;
        });
    }
}
