<?php

namespace App\Services\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserSex;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserService
{
    /**
     * @param  array{name: string, email: string, password: string, sex: string|UserSex, locale?: string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $sex = $data['sex'] instanceof UserSex
                ? $data['sex']
                : UserSex::from((string) $data['sex']);

            $user = new User([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'locale' => $data['locale'] ?? app()->getLocale(),
                'sex' => $sex,
            ]);
            $user->forceFill([
                'status' => AccountStatus::PendingDocuments,
            ])->save();

            return $user;
        });
    }
}
