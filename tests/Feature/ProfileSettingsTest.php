<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_name_and_password(): void
    {
        $user = User::factory()->active()->create([
            'name' => 'Old Name',
            'email' => 'member@example.com',
            'password' => 'Password1!',
        ]);

        $this->actingAs($user)
            ->put(route('settings.profile.update'), [
                'name' => 'New Name',
                'email' => 'member@example.com',
                'sex' => $user->sex?->value ?? 'male',
                'locale' => 'en',
                'current_password' => 'Password1!',
                'password' => 'Password2!',
                'password_confirmation' => 'Password2!',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertTrue(Hash::check('Password2!', $user->password));
    }

    public function test_email_change_requires_verification(): void
    {
        Mail::fake();

        $user = User::factory()->active()->create([
            'email' => 'old@example.com',
            'password' => 'Password1!',
        ]);

        $this->actingAs($user)
            ->put(route('settings.profile.update'), [
                'name' => $user->name,
                'email' => 'new@example.com',
                'sex' => $user->sex?->value ?? 'female',
                'locale' => 'en',
            ])
            ->assertRedirect(route('verification.notice'));

        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake(config('media.disk', 'media'));

        $user = User::factory()->active()->create([
            'password' => 'Password1!',
        ]);

        $this->actingAs($user)
            ->put(route('settings.profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'sex' => $user->sex?->value ?? 'mix',
                'locale' => 'en',
                'photo' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->profile_image_path);
    }
}
