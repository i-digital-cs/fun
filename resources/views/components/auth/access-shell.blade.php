@props([
    'action',
    'mode' => 'form', // form | otp
    'digitSource' => 'password', // password | code | email | none
    'title',
    'subtitle' => null,
    'enterTitle' => null,
    'enterSub' => null,
    'verifyingTitle' => null,
    'verifyingSub' => null,
    'grantedTitle' => null,
    'grantedSub' => null,
    'resendUrl' => null,
    'showGoogle' => false,
    'footerForm' => null,
])

@php
    $enterTitle = $enterTitle ?? __('ui.access_enter_title');
    $enterSub = $enterSub ?? __('ui.access_enter_sub');
    $verifyingTitle = $verifyingTitle ?? __('ui.access_verifying_title');
    $verifyingSub = $verifyingSub ?? __('ui.access_verifying_sub');
    $grantedTitle = $grantedTitle ?? __('ui.access_granted_title');
    $grantedSub = $grantedSub ?? __('ui.access_granted_sub');
    $brand = $siteName ?? site_display_name();
    $logo = $siteLogoUrl ?? site_logo_url();
@endphp

<div
    {{ $attributes->class(['login-access mx-auto w-full max-w-[22rem]']) }}
    data-auth-access
    data-action-url="{{ $action }}"
    data-csrf="{{ csrf_token() }}"
    data-mode="{{ $mode }}"
    data-digit-source="{{ $digitSource }}"
    @if ($resendUrl) data-resend-url="{{ $resendUrl }}" @endif
>
    <script type="application/json" data-auth-i18n>
        {!! json_encode([
            'enter_title' => $enterTitle,
            'enter_sub' => $enterSub,
            'verifying_title' => $verifyingTitle,
            'verifying_sub' => $verifyingSub,
            'granted_title' => $grantedTitle,
            'granted_sub' => $grantedSub,
            'verified' => __('ui.access_verified'),
            'resend_prompt' => __('ui.access_resend_prompt'),
            'resend' => __('ui.access_resend'),
            'resend_ok' => __('auth.code_sent'),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
    </script>

    <div class="login-access__card relative overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white p-8 shadow-[0_18px_50px_-24px_rgba(79,70,229,0.35)] sm:p-10">
        <div class="mb-5 flex flex-col items-center text-center">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $brand }}" class="mb-3 h-12 max-w-[9rem] object-contain">
            @else
                <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/30">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                </span>
            @endif
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-500">{{ $brand }}</p>
        </div>

        <div class="text-center">
            <h1 data-auth-title class="text-[1.35rem] font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                {{ $title }}
            </h1>
            @if ($subtitle)
                <p data-auth-sub class="mt-1.5 text-sm text-slate-400">{{ $subtitle }}</p>
            @else
                <p data-auth-sub class="mt-1.5 text-sm text-slate-400"></p>
            @endif
        </div>

        <div data-auth-error class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert"></div>

        @if ($mode === 'otp')
            <form data-stage="form" method="POST" action="{{ $action }}" class="mt-7 space-y-4" autocomplete="one-time-code">
                @csrf
                <input type="hidden" name="code" data-otp-value value="">
                <div class="login-access__digits flex items-center justify-center gap-3" data-otp-inputs>
                    @for ($i = 0; $i < 4; $i++)
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            data-otp-input="{{ $i }}"
                            class="login-access__digit login-access__digit-input is-empty text-center focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            aria-label="{{ __('ui.access_digit', ['n' => $i + 1]) }}"
                        >
                    @endfor
                </div>
                {{ $slot }}
                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 font-extrabold text-white shadow-lg shadow-indigo-600/25 transition hover:brightness-105">
                    {{ __('ui.access_verify_btn') }}
                </button>
            </form>
        @else
            <form data-stage="form" method="POST" action="{{ $action }}" class="mt-7 space-y-4">
                @csrf
                {{ $slot }}
            </form>
        @endif

        <div data-stage="theater" class="mt-8 hidden" aria-live="polite">
            <div class="login-access__stage relative mx-auto flex h-44 w-full items-center justify-center">
                <div data-digits class="login-access__digits absolute inset-0 flex items-center justify-center gap-3">
                    <span class="login-access__digit" data-digit="0"></span>
                    <span class="login-access__digit" data-digit="1"></span>
                    <span class="login-access__digit" data-digit="2"></span>
                    <span class="login-access__digit" data-digit="3"></span>
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

        <div data-footer-form class="mt-4 space-y-3">
            {{ $footerForm }}
            @if ($showGoogle)
                <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
                    {{ __('ui.continue_google') }}
                </a>
            @endif
        </div>

        <p data-footer-theater class="mt-6 hidden text-center text-sm text-slate-400">
            <span>{{ __('ui.access_resend_prompt') }}</span>
            <button type="button" data-auth-resend class="ms-1 font-semibold text-indigo-500 transition hover:text-indigo-700">
                {{ __('ui.access_resend') }}
            </button>
        </p>
    </div>
</div>
