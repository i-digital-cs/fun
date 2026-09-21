<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function download(IdentityDocument $document): StreamedResponse
    {
        $this->authorize('viewIdentityDocuments', $document->user);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->response(
            $document->path,
            basename($document->path),
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline',
            ]
        );
    }
}
