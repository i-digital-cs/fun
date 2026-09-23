<?php

namespace App\Http\Controllers\Api\Identity;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\StoreIdentityDocumentsRequest;
use App\Models\IdentityDocument;
use App\Services\Identity\SubmitIdentityDocumentsService;
use App\Services\Media\PrivateMediaAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdentityDocumentController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('identityDocuments');

        $documents = $user->identityDocuments->map(fn (IdentityDocument $doc) => [
            'id' => $doc->id,
            'type' => $doc->type->value,
            'mime_type' => $doc->mime_type,
            'size' => $doc->size,
            'url' => url('/api/identity/documents/'.$doc->id),
        ])->values();

        return response()->json([
            'status' => $user->status->value,
            'needs_upload' => $user->canUploadIdentityDocuments(),
            'resubmit_required' => $user->identityResubmitRequired(),
            'has_identity_documents' => $user->hasRequiredIdentityDocuments(),
            'documents' => $documents,
        ]);
    }

    public function store(
        StoreIdentityDocumentsRequest $request,
        SubmitIdentityDocumentsService $service,
    ): JsonResponse {
        try {
            $user = $service->submit($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('identity.activated'),
            'user' => [
                'id' => $user->id,
                'status' => $user->status->value,
            ],
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
}
