@extends('layouts.admin')

@section('title', __('admin.identity_documents').' — '.$user->name)

@section('content')

    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ $user->name }}</h1>
            <p class="text-sm text-muted">{{ $user->email }} · {{ $user->status->value }}</p>
        </div>
        @if ($user->isActive() && $user->hasRequiredIdentityDocuments())
            <form method="POST" action="{{ route('admin.identity.request-resubmit', $user) }}"
                  data-confirm="{{ __('identity.admin_resubmit_confirm') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                    {{ __('identity.admin_request_resubmit') }}
                </button>
            </form>
        @elseif ($user->needsIdentityDocuments())
            <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                {{ __('identity.resubmit_required') }}
            </span>
        @endif
    </div>

    @error('identity')
        <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
    @enderror

    @if (session('status'))
        <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="mt-2 flex flex-wrap gap-10">
        @if ($documents->isEmpty())
            <p class="text-muted">{{ __('admin.no_documents') }}</p>
        @else
            @php
                $adminFiles = $documents->map(function ($document) {
                    $ext = strtoupper(pathinfo((string) $document->path, PATHINFO_EXTENSION) ?: 'IMG');
                    $kb = $document->size ? number_format($document->size / 1024, 1).' KB' : null;

                    return [
                        'name' => $document->type->value,
                        'url' => route('admin.identity.download', $document),
                        'tag' => collect([$ext, $kb])->filter()->implode(' · '),
                    ];
                })->values()->all();
            @endphp
            <x-ui.attachment-folder
                mode="view"
                :label="__('admin.documents')"
                :files="$adminFiles"
            />
        @endif
    </div>
@endsection
