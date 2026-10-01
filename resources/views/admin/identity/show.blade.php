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

    @php
        $typeOrder = ['id_front' => 0, 'id_back' => 1, 'selfie' => 2];
        $ordered = $documents->sortBy(fn ($d) => $typeOrder[$d->type->value] ?? 99)->values();
    @endphp

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-base font-extrabold tracking-tight text-slate-900">{{ __('admin.documents') }}</h2>
        @if ($ordered->isNotEmpty())
            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-indigo-700 ring-1 ring-inset ring-indigo-100">
                {{ __('admin.files_count', ['count' => $ordered->count()]) }}
            </span>
        @endif
    </div>

    @if ($ordered->isEmpty())
        <p class="text-muted">{{ __('admin.no_documents') }}</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($ordered as $document)
                @php
                    $ext = strtoupper(pathinfo((string) $document->path, PATHINFO_EXTENSION) ?: 'FILE');
                    $kb = $document->size ? number_format($document->size / 1024, 1).' KB' : null;
                    $mime = (string) ($document->mime_type ?? '');
                    $isImage = str_starts_with($mime, 'image/') || in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                    $url = route('admin.identity.download', $document);
                    $typeLabel = __('identity.'.$document->type->value);
                    $fileName = basename((string) $document->path);
                @endphp
                <article class="overflow-hidden rounded-[18px] border border-[#e7ebf3] bg-white shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-extrabold text-slate-900">{{ $typeLabel }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ collect([$ext, $kb])->filter()->implode(' · ') }}</p>
                    </div>

                    <div class="bg-slate-50 p-3">
                        @if ($isImage)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <img
                                    src="{{ $url }}"
                                    alt="{{ $typeLabel }}"
                                    class="mx-auto max-h-80 w-full object-contain"
                                    loading="lazy"
                                >
                            </a>
                        @else
                            <a
                                href="{{ $url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex min-h-40 flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center no-underline transition hover:border-indigo-300 hover:bg-indigo-50/40"
                            >
                                <span class="text-2xl font-black text-indigo-600">{{ $ext }}</span>
                                <span class="text-sm font-semibold text-slate-700">{{ __('admin.view_document') }}</span>
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-2 px-4 py-3">
                        <p class="truncate text-xs text-slate-500" title="{{ $fileName }}">{{ $fileName }}</p>
                        <a
                            href="{{ $url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white no-underline transition hover:bg-indigo-500"
                        >
                            {{ __('admin.view_document') }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
