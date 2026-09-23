<?php

namespace App\Http\Controllers\Api\Identity;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\StoreIdentityDocumentsRequest;
use App\Models\IdentityDocument;
use App\Models\User;
use App\Services\Identity\SubmitIdentityDocumentsService;
use App\Services\Media\PrivateMediaAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdentityDocumentController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload($request->user()));
    }

    public function store(
        StoreIdentityDocumentsRequest $request,
        SubmitIdentityDocumentsService $service,
    ): JsonResponse {
        try {
            $user = $service->submit($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['identity' => [$exception->getMessage()]],
            ], 422);
        }

        return response()->json([
            'message' => __('identity.activated'),
            ...$this->payload($user),
        ]);
    }

    public function showDocument(
        Request $request,
        IdentityDocument $document,
        PrivateMediaAccessService $access,
    ): StreamedResponse {
        $document->loadMissing('user');

        $this->authorize('viewOwnIdentityDocument', $document->user);

        return $access->stream($document);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        $user->loadMissing('identityDocuments');

        $byType = $user->identityDocuments->keyBy(
            fn (IdentityDocument $doc) => $doc->type->value
        );

        $documents = collect(['id_front', 'id_back', 'selfie'])->map(function (string $type) use ($byType) {
            $doc = $byType->get($type);

            if ($doc === null) {
                return [
                    'type' => $type,
                    'id' => null,
                    'mime_type' => null,
                    'size' => null,
                    'url' => null,
                    'uploaded' => false,
                ];
            }

            return [
                'type' => $type,
                'id' => $doc->id,
                'mime_type' => $doc->mime_type,
                'size' => $doc->size,
                'url' => url('/api/identity/documents/'.$doc->id),
                'uploaded' => true,
            ];
        })->values();

        return [
            'status' => $user->status->value,
            'email_verified' => $user->email_verified_at !== null,
            'needs_email_verification' => $user->email_verified_at === null,
            'needs_upload' => $user->canUploadIdentityDocuments(),
            'can_upload' => $user->canUploadIdentityDocuments(),
            'needs_identity_documents' => $user->needsIdentityDocuments(),
            'resubmit_required' => $user->identityResubmitRequired(),
            'has_identity_documents' => $user->hasRequiredIdentityDocuments(),
            'documents' => $documents,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'email_verified' => $user->email_verified_at !== null,
                'needs_identity_documents' => $user->needsIdentityDocuments(),
                'has_identity_documents' => $user->hasRequiredIdentityDocuments(),
                'identity_resubmit_required' => $user->identityResubmitRequired(),
            ],
        ];
    }
}
