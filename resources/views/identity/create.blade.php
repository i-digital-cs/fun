@extends('layouts.dashboard')

@section('title', __('identity.title'))

@section('content')
    @php
        $brand = $appName ?? site_display_name();
        $logo = $siteLogoUrl ?? site_logo_url();
    @endphp

    <div
        class="login-access mx-auto max-w-xl"
        data-auth-access
        data-action-url="{{ route('identity.store') }}"
        data-csrf="{{ csrf_token() }}"
        data-mode="form"
        data-digit-source="none"
    >
        <script type="application/json" data-auth-i18n>
            {!! json_encode([
                'enter_title' => __('identity.theater_enter_title'),
                'enter_sub' => __('identity.theater_enter_sub'),
                'verifying_title' => __('identity.theater_verifying_title'),
                'verifying_sub' => __('identity.theater_verifying_sub'),
                'granted_title' => __('identity.theater_granted_title'),
                'granted_sub' => __('identity.theater_granted_sub'),
                'verified' => __('ui.access_verified'),
                'resend_prompt' => __('ui.cancel'),
                'resend' => __('ui.cancel'),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
        </script>

        <div class="login-access__card relative overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white p-6 shadow-[0_18px_50px_-24px_rgba(79,70,229,0.28)] sm:p-8">
            <div class="mb-5 flex flex-col items-center text-center">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $brand }}" class="mb-3 h-12 max-w-[9rem] object-contain">
                @else
                    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/30">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <circle cx="9" cy="12" r="2.5"/>
                            <path stroke-linecap="round" d="M14 10h4M14 14h3"/>
                        </svg>
                    </span>
                @endif
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-500">{{ $brand }}</p>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-3 text-center sm:text-start">
                <div class="min-w-0 flex-1">
                    <h1 data-auth-title class="text-[1.35rem] font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                        {{ __('identity.title') }}
                    </h1>
                    <p data-auth-sub class="mt-1.5 text-sm text-slate-400">{{ __('identity.help') }}</p>
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

            <div data-auth-error class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert"></div>

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
                <form data-stage="form" method="POST" action="{{ route('identity.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
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
                            <div class="overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50/80">
                                <img data-preview-for="{{ $name }}" alt="" class="hidden aspect-[4/3] w-full object-cover">
                                <input id="{{ $name }}" type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" required
                                       data-identity-file="{{ $name }}"
                                       class="w-full px-4 py-8 text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700">
                            </div>
                            @error($name)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                        {{ $resubmitRequired ? __('identity.resubmit') : __('identity.submit') }}
                    </button>
                </form>

                <div data-stage="theater" class="mt-8 hidden" aria-live="polite">
                    <div class="login-access__stage relative mx-auto flex h-44 w-full items-center justify-center">
                        <div data-digits class="login-access__digits absolute inset-0 flex items-center justify-center gap-3">
                            <span class="login-access__digit" data-digit="0">ID</span>
                            <span class="login-access__digit" data-digit="1">••</span>
                            <span class="login-access__digit" data-digit="2">••</span>
                            <span class="login-access__digit" data-digit="3">OK</span>
                        </div>
                        <div data-verify class="login-access__verify pointer-events-none absolute inset-0 flex items-center justify-center opacity-0">
                            <div class="login-access__ring relative flex h-28 w-28 items-center justify-center">
                                <svg class="login-access__ring-svg absolute inset-0 h-full w-full" viewBox="0 0 120 120" aria-hidden="true">
                                    <circle class="login-access__ring-track" cx="60" cy="60" r="52" fill="none" stroke-width="6" />
                                    <circle data-ring-progress class="login-access__ring-progress" cx="60" cy="60" r="52" fill="none" stroke-width="7" stroke-linecap="round" />
                                </svg>
                                <span data-count class="relative text-3xl font-semibold tabular-nums text-slate-400">0</span>
                            </div>
                        </div>
                        <div data-success class="login-access__success pointer-events-none absolute inset-0 flex flex-col items-center justify-center opacity-0">
                            <div data-confetti class="login-access__confetti absolute inset-0 overflow-hidden" aria-hidden="true"></div>
                            <div class="login-access__check relative flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500 shadow-[0_0_0_12px_rgba(16,185,129,0.18)]">
                                <svg class="h-9 w-9 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="mt-4 inline-flex rounded-full bg-emerald-500 px-3.5 py-1 text-[0.7rem] font-extrabold tracking-wider text-white">
                                {{ __('ui.access_verified') }}
                            </span>
                        </div>
                    </div>
                </div>

                <p data-footer-theater class="mt-6 hidden text-center text-sm text-slate-400">
                    <button type="button" data-auth-resend class="font-semibold text-indigo-500 transition hover:text-indigo-700">
                        {{ __('ui.cancel') }}
                    </button>
                </p>
            @elseif ($user->isActive() && $user->hasRequiredIdentityDocuments())
                <p class="mt-6 text-sm text-slate-500">{{ __('identity.submitted_help') }}</p>
                <a href="{{ route('dashboard') }}" class="mt-4 inline-flex rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-extrabold text-white no-underline shadow-lg shadow-indigo-600/25">
                    {{ __('dashboard.nav_home') }}
                </a>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/auth-access.js'])
    <script>
        document.querySelectorAll('[data-identity-file]').forEach((input) => {
            input.addEventListener('change', () => {
                const preview = document.querySelector(`[data-preview-for="${input.dataset.identityFile}"]`);
                const file = input.files?.[0];
                if (!preview || !file) return;
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
            });
        });
    </script>
@endpush
