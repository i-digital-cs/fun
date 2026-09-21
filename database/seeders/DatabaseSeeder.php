<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CitySeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'status' => AccountStatus::Active,
                'platform_role' => PlatformRole::SuperAdmin,
                'locale' => 'en',
                'email_verified_at' => now(),
            ]
        );
    }
}
