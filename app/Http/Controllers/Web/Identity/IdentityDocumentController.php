<?php

namespace App\Http\Controllers\Web\Identity;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\StoreIdentityDocumentsRequest;
use App\Models\Category;
use App\Models\IdentityDocument;
use App\Services\Identity\SubmitIdentityDocumentsService;
use App\Services\Media\PrivateMediaAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdentityDocumentController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user()->load('identityDocuments');

        $documents = $user->identityDocuments
            ->keyBy(fn (IdentityDocument $doc) => $doc->type->value);

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        return view('identity.create', [
            'user' => $user,
            'documents' => $documents,
            'canUpload' => $user->canUploadIdentityDocuments(),
            'resubmitRequired' => $user->identityResubmitRequired(),
            'categories' => $categories,
            'appName' => site_display_name(),
        ]);
    }

    public function store(
        StoreIdentityDocumentsRequest $request,
        SubmitIdentityDocumentsService $service,
    ): RedirectResponse|JsonResponse {
        try {
            $user = $service->submit($request->user(), $request->validated());
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['identity' => [$exception->getMessage()]],
                ], 422);
            }

            return back()->withErrors(['identity' => $exception->getMessage()]);
        }

        // NAV-W3: honor session intended URL through the identity gate.
        $redirect = redirect()->intended(route($user->defaultHomeRoute()))
            ->with('status', __('identity.activated'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('identity.activated'),
                'redirect' => $redirect->getTargetUrl(),
                'user' => [
                    'id' => $user->id,
                    'status' => $user->status->value,
                    'needs_identity_documents' => $user->needsIdentityDocuments(),
                    'has_identity_documents' => $user->hasRequiredIdentityDocuments(),
                    'identity_resubmit_required' => $user->identityResubmitRequired(),
                ],
            ]);
        }

        return $redirect;
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
