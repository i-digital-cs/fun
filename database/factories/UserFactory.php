<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\PlatformRole;
use App\Enums\UserSex;
use App\Models\User;
use App\Support\UserPublicId;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => UserPublicId::generate(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => AccountStatus::PendingDocuments,
            'locale' => 'en',
            'sex' => fake()->randomElement(UserSex::cases()),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Active,
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Deactivated,
            'deactivated_at' => now(),
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Active,
            'platform_role' => PlatformRole::SuperAdmin,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Active,
            'platform_role' => PlatformRole::Moderator,
        ]);
    }
}
