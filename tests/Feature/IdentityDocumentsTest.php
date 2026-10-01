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

        $response->assertRedirect(route('dashboard'));
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

    public function test_owner_can_stream_own_document_and_outsider_cannot(): void
    {
        Storage::fake(config('media.private_disk'));

        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $document = $owner->fresh()->identityDocuments()->firstOrFail();

        $this->actingAs($owner)
            ->get(route('identity.documents.show', $document))
            ->assertOk();

        $outsider = User::factory()->active()->create();
        $this->actingAs($outsider)
            ->get(route('identity.documents.show', $document))
            ->assertForbidden();
    }

    public function test_admin_identity_show_lists_each_document_separately(): void
    {
        Storage::fake(config('media.private_disk'));

        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)
            ->get(route('admin.identity.show', $owner))
            ->assertOk()
            ->assertSee(__('identity.id_front'), false)
            ->assertSee(__('identity.id_back'), false)
            ->assertSee(__('identity.selfie'), false)
            ->assertSee(__('admin.files_count', ['count' => 3]), false)
            ->assertDontSee('attachment-folder', false);
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

    public function test_active_user_cannot_reupload_without_admin_request(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $this->actingAs($user->fresh())->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front2.jpg'),
            'id_back' => UploadedFile::fake()->image('back2.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie2.jpg'),
        ])->assertSessionHasErrors('identity');
    }

    public function test_admin_request_resubmit_then_user_reuploads(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $oldPath = $user->fresh()->identityDocuments()->where('type', IdentityDocumentType::IdFront)->firstOrFail()->path;

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)
            ->post(route('admin.identity.request-resubmit', $user))
            ->assertRedirect();

        $user->refresh();
        $this->assertSame(AccountStatus::PendingDocuments, $user->status);
        $this->assertFalse($user->canParticipate());
        $this->assertTrue($user->identityResubmitRequired());

        $this->actingAs($user)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front-new.jpg'),
            'id_back' => UploadedFile::fake()->image('back-new.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie-new.jpg'),
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertSame(AccountStatus::Active, $user->status);
        $this->assertTrue($user->canParticipate());

        $newPath = $user->identityDocuments()->where('type', IdentityDocumentType::IdFront)->firstOrFail()->path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk(config('media.private_disk'))->assertMissing($oldPath);
        Storage::disk(config('media.private_disk'))->assertExists($newPath);
    }

    public function test_cannot_request_resubmit_for_deactivated_user(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $admin = User::factory()->superAdmin()->create();
        $user->forceFill([
            'status' => AccountStatus::Deactivated,
            'deactivated_at' => now(),
        ])->save();

        $this->actingAs($admin)
            ->post(route('admin.identity.request-resubmit', $user))
            ->assertSessionHasErrors('identity');
    }

    public function test_api_identity_show_lists_documents(): void
    {
        Storage::fake(config('media.private_disk'));

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('identity.store'), [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
            'id_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $this->actingAs($user->fresh(), 'sanctum')
            ->getJson('/api/identity')
            ->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('needs_upload', false)
            ->assertJsonCount(3, 'documents');
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
