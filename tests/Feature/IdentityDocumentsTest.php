<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_required_documents_activates_account(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect(route('home'));
        $user->refresh();

        $this->assertSame(AccountStatus::Active, $user->status);
        $this->assertCount(3, $user->identityDocuments);
        $this->assertTrue($user->hasRequiredIdentityDocuments());

        foreach (IdentityDocumentType::required() as $type) {
            Storage::disk(config('media.private_disk'))->assertExists(
                $user->identityDocuments()->where('type', $type)->first()->path
            );
        }
    }

    public function test_api_identity_upload_activates_account(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/identity', [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()->assertJsonPath('user.status', 'active');
    }

    public function test_admin_can_download_identity_document_and_outsider_cannot(): void
    {
        Storage::fake(config('media.private_disk'));

        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $document = $owner->fresh()->identityDocuments()->firstOrFail();

        $outsider = User::factory()->active()->create();
        $this->actingAs($outsider)
            ->get(route('admin.identity.download', $document))
            ->assertForbidden();

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)
            ->get(route('admin.identity.download', $document))
            ->assertOk();
    }

    public function test_identity_upload_uses_configured_private_disk_name(): void
    {
        config(['media.private_disk' => 'r2_private']);
        Storage::fake('r2_private');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $document = $user->fresh()->identityDocuments()->firstOrFail();
        $this->assertSame('r2_private', $document->disk);
        Storage::disk('r2_private')->assertExists($document->path);
    }
}
