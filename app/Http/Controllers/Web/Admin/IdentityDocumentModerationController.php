<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\IdentityDocument;
use App\Models\User;
use App\Services\Identity\RequestIdentityResubmitService;
use App\Services\Media\PrivateMediaAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdentityDocumentModerationController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->whereHas('identityDocuments')
            ->withCount('identityDocuments')
            ->latest()
            ->paginate(20);

        return view('admin.identity.index', compact('users'));
    }

    public function show(User $user): View
    {
        $this->authorize('viewIdentityDocuments', $user);

        $documents = $user->identityDocuments()->latest()->get();

        return view('admin.identity.show', compact('user', 'documents'));
    }

    public function download(
        IdentityDocument $document,
        PrivateMediaAccessService $access,
    ): StreamedResponse {
        $document->loadMissing('user');

        $this->authorize('viewIdentityDocuments', $document->user);

        return $access->stream($document);
    }

    public function requestResubmit(
        User $user,
        RequestIdentityResubmitService $service,
    ): RedirectResponse {
        $this->authorize('requestIdentityResubmit', $user);

        try {
            $service->request(request()->user(), $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['identity' => $exception->getMessage()]);
        }

        return back()->with('status', __('identity.admin_resubmit_requested'));
    }
}
