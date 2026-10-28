<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthIntendedRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_submit_honors_session_intended_url(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();
        $event = Event::factory()->published()->create();
        $intended = route('events.show', $event);

        $this->actingAs($user)
            ->withSession(['url.intended' => $intended])
            ->post(route('identity.store'), [
                'id_front' => UploadedFile::fake()->image('front.jpg'),
                'id_back' => UploadedFile::fake()->image('back.jpg'),
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            ])
            ->assertRedirect($intended);
    }

    public function test_identity_submit_falls_back_to_dashboard_without_intended(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('identity.store'), [
                'id_front' => UploadedFile::fake()->image('front.jpg'),
                'id_back' => UploadedFile::fake()->image('back.jpg'),
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            ])
            ->assertRedirect(route('dashboard'));
    }
}
