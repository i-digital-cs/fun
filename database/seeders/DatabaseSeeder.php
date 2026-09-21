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
        $this->call([
            CitySeeder::class,
            CategorySeeder::class,
        ]);

        $admin = User::query()->firstOrNew(['email' => 'admin@example.com']);
        $admin->fill([
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'locale' => 'en',
        ]);
        $admin->forceFill([
            'status' => AccountStatus::Active,
            'platform_role' => PlatformRole::SuperAdmin,
            'email_verified_at' => now(),
        ])->save();
    }
}
