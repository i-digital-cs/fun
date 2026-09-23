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
                  onsubmit="return confirm(@json(__('identity.admin_resubmit_confirm')))">
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

    <div class="space-y-3">
        @forelse ($documents as $document)
            <div class="flex items-center justify-between gap-3 rounded-xl border border-border bg-surface p-4">
                <div>
                    <p class="font-medium">{{ $document->type->value }}</p>
                    <p class="text-sm text-muted">{{ $document->mime_type }} · {{ number_format($document->size / 1024, 1) }} KB</p>
                </div>
                <a href="{{ route('admin.identity.download', $document) }}" class="rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white" target="_blank" rel="noopener">
                    {{ __('admin.view_document') }}
                </a>
            </div>
        @empty
            <p class="text-muted">{{ __('admin.no_documents') }}</p>
        @endforelse
    </div>
@endsection
