<?php

namespace App\Http\Controllers\Web\Identity;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\StoreIdentityDocumentsRequest;
use App\Services\Identity\SubmitIdentityDocumentsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IdentityDocumentController extends Controller
{
    public function create(): View
    {
        return view('identity.create');
    }

    public function store(
        StoreIdentityDocumentsRequest $request,
        SubmitIdentityDocumentsService $service,
    ): RedirectResponse {
        try {
            $service->submit($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return back()->withErrors(['identity' => $exception->getMessage()]);
        }

        return redirect()->route('dashboard')
            ->with('status', __('identity.activated'));
    }
}
