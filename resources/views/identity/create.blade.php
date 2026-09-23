@extends('layouts.dashboard')

@section('title', __('identity.title'))

@section('content')
    <div class="mx-auto max-w-xl space-y-6">
        <div class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('identity.title') }}</h1>
                    <p class="mt-2 text-sm text-slate-500">{{ __('identity.help') }}</p>
                </div>
                @if ($resubmitRequired)
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                        {{ __('identity.resubmit_required') }}
                    </span>
                @elseif ($user->isActive() && $user->hasRequiredIdentityDocuments())
                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                        {{ __('identity.submitted') }}
                    </span>
                @elseif ($user->needsIdentityDocuments())
                    <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                        {{ __('identity.pending_upload') }}
                    </span>
                @endif
            </div>

            @if ($resubmitRequired)
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ __('identity.resubmit_banner') }}
                </div>
            @endif

            @if ($documents->isNotEmpty())
                <div class="mt-6">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('identity.view_documents') }}</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        @foreach (['id_front' => __('identity.id_front'), 'id_back' => __('identity.id_back'), 'selfie' => __('identity.selfie')] as $type => $label)
                            @php($doc = $documents->get($type))
                            <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-slate-50">
                                <p class="border-b border-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">{{ $label }}</p>
                                @if ($doc)
                                    <a href="{{ route('identity.documents.show', $doc) }}" target="_blank" rel="noopener" class="block">
                                        <img src="{{ route('identity.documents.show', $doc) }}"
                                             alt="{{ $label }}"
                                             class="aspect-[4/3] w-full object-cover">
                                    </a>
                                @else
                                    <div class="flex aspect-[4/3] items-center justify-center text-xs text-slate-400">—</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($canUpload)
                <form method="POST" action="{{ route('identity.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                    @csrf
                    @error('identity')
                        <p class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror

                    @foreach ([
                        'id_front' => __('identity.id_front'),
                        'id_back' => __('identity.id_back'),
                        'selfie' => __('identity.selfie'),
                    ] as $name => $label)
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="{{ $name }}">{{ $label }}</label>
                            <input id="{{ $name }}" type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" required
                                   class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-8 text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700">
                            @error($name)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                        {{ $resubmitRequired ? __('identity.resubmit') : __('identity.submit') }}
                    </button>
                </form>
            @elseif ($user->isActive() && $user->hasRequiredIdentityDocuments())
                <p class="mt-6 text-sm text-slate-500">{{ __('identity.submitted_help') }}</p>
            @endif
        </div>
    </div>
@endsection
