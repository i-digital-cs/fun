<?php

namespace App\Services\Identity;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentType;
use App\Exceptions\DomainException;
use App\Models\IdentityDocument;
use App\Models\User;
use App\Services\Media\PrivateMediaUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SubmitIdentityDocumentsService
{
    public function __construct(
        private readonly PrivateMediaUploader $uploader,
    ) {}

    /**
     * @param  array{id_front: UploadedFile, id_back: UploadedFile, selfie: UploadedFile}  $files
     */
    public function submit(User $user, array $files): User
    {
        if ($user->isDeactivated()) {
            throw new DomainException(__('identity.deactivated'));
        }

        if (! $user->status->canUploadIdentityDocuments() && ! $user->isActive()) {
            throw new DomainException(__('identity.not_allowed'));
        }

        if ($user->isActive() && $user->hasRequiredIdentityDocuments()) {
            throw new DomainException(__('identity.already_submitted'));
        }

        return DB::transaction(function () use ($user, $files) {
            foreach (IdentityDocumentType::required() as $type) {
                $key = $type->value;
                /** @var UploadedFile $file */
                $file = $files[$key];
                $stored = $this->uploader->storeIdentityDocument($file, $user->id, $type->value);

                $existing = IdentityDocument::query()
                    ->where('user_id', $user->id)
                    ->where('type', $type)
                    ->first();

                if ($existing) {
                    $this->uploader->delete($existing->disk, $existing->path);
                    $existing->update($stored);
                } else {
                    IdentityDocument::query()->create([
                        'user_id' => $user->id,
                        'type' => $type,
                        ...$stored,
                    ]);
                }
            }

            $user->forceFill([
                'status' => AccountStatus::Active,
            ])->save();

            return $user->refresh();
        });
    }
}
