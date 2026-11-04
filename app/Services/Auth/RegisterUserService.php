<?php

namespace App\Services\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserSex;
use App\Exceptions\RegistrationBlockedException;
use App\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterUserService
{
    public function __construct(
        private readonly RegistrationSecurityGate $securityGate,
    ) {
    }

    /**
     * @param  array{name: string, email: string, password: string, sex: string|UserSex, locale?: string}  $data
     *
     * @throws RegistrationBlockedException
     */
    public function register(array $data, ?Request $request = null): User
    {
        $request ??= request();
        $email = (string) $data['email'];
        $name = (string) ($data['name'] ?? '');

        $lock = $this->securityGate->authorize($request, $email, $name);

        try {
            $user = DB::transaction(function () use ($data, $email): User {
                $sex = $data['sex'] instanceof UserSex
                    ? $data['sex']
                    : UserSex::from((string) $data['sex']);

                $user = new User([
                    'name' => $data['name'],
                    'email' => strtolower($email),
                    'password' => $data['password'],
                    'locale' => $data['locale'] ?? app()->getLocale(),
                    'sex' => $sex,
                ]);
                $user->forceFill([
                    'status' => AccountStatus::PendingDocuments,
                ])->save();

                return $user;
            });

            $this->securityGate->recordAccepted($request, $email);

            return $user;
        } finally {
            $this->releaseLock($lock);
        }
    }

    private function releaseLock(Lock $lock): void
    {
        try {
            $lock->release();
        } catch (\Throwable) {
            // Lock may already have expired; ignore.
        }
    }
}
