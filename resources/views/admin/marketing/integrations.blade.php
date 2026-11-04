@extends('layouts.admin')

@section('title', __('marketing.integrations_heading'))

@push('head')
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=tajawal:400,500,600,700&display=swap">
    <style>
        html[lang^="ar"] .marketing-shell { font-family: Tajawal, "IBM Plex Sans Arabic", sans-serif; }
    </style>
@endpush

@section('content')
<div class="marketing-shell space-y-6">
    @include('admin.marketing._nav')

    <header>
        <h1 class="text-2xl font-extrabold tracking-tight text-[#19213D]">{{ __('marketing.integrations_heading') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-600">{{ __('marketing.integrations_help') }}</p>
    </header>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($integrations as $integration)
            <article class="rounded-2xl border border-[#E8EAF2] bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-[#19213D]">{{ $integration['name'] }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ strtoupper($integration['code']) }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                        {{ __('marketing.status_not_connected') }}
                    </span>
                </div>

                <p class="mt-4 text-sm text-slate-600">{{ __('marketing.runtime_not_ready') }}</p>

                <div class="mt-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">{{ __('marketing.planned_capabilities') }}</p>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($integration['capabilities'] as $capability)
                            <li class="rounded-lg bg-[#F1EBFF] px-2 py-1 text-[11px] font-semibold text-[#5825D6]">{{ $capability }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-6">
                    <button
                        type="button"
                        disabled
                        aria-disabled="true"
                        title="{{ __('marketing.connect_disabled_hint') }}"
                        class="inline-flex cursor-not-allowed items-center rounded-xl bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-500"
                    >
                        {{ __('marketing.connect_disabled') }}
                    </button>
                    <p class="mt-2 text-xs text-slate-500">{{ __('marketing.connect_disabled_hint') }}</p>
                </div>
            </article>
        @endforeach
    </div>
</div>
@endsection
