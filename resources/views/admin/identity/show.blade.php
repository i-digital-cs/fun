@extends('layouts.admin')

@section('title', __('admin.identity_documents').' — '.$user->name)

@section('content')

    <div class="mb-4">
        <h1 class="text-2xl font-semibold">{{ $user->name }}</h1>
        <p class="text-sm text-muted">{{ $user->email }} · {{ $user->status->value }}</p>
    </div>

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
