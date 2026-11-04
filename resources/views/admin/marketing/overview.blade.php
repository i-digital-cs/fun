@extends('layouts.admin')

@section('title', __('marketing.overview_heading'))

@push('head')
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=tajawal:400,500,600,700&display=swap">
    <style>
        html[lang^="ar"] .marketing-shell { font-family: Tajawal, "IBM Plex Sans Arabic", sans-serif; }
    </style>
@endpush

@section('content')
<div class="marketing-shell space-y-6">
    @include('admin.marketing._nav')

    <header class="rounded-2xl border border-[#E8EAF2] bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-widest text-[#7138F5]">{{ __('marketing.phase_badge', ['phase' => $overview['phase']]) }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#19213D]">{{ __('marketing.manager_title') }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ __('marketing.manager_subtitle', ['phase' => $overview['phase']]) }}</p>
            </div>
        </div>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => __('marketing.flag_enabled'), 'on' => $overview['module_enabled']],
            ['label' => __('marketing.flag_read_only'), 'on' => $overview['read_only']],
            ['label' => __('marketing.flag_execution'), 'on' => $overview['execution_enabled']],
            ['label' => __('marketing.flag_ai'), 'on' => $overview['ai_explanations_enabled']],
        ] as $flag)
            <div class="rounded-2xl border border-[#E8EAF2] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-500">{{ $flag['label'] }}</p>
                <p @class([
                    'mt-2 text-lg font-extrabold',
                    'text-emerald-700' => $flag['on'],
                    'text-slate-500' => ! $flag['on'],
                ])>
                    {{ $flag['on'] ? __('marketing.flag_on') : __('marketing.flag_off') }}
                </p>
            </div>
        @endforeach
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-[#E8EAF2] bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-[#19213D]">{{ __('marketing.readiness') }}</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($overview['integrations'] as $integration)
                    <li class="flex items-center justify-between gap-3 rounded-xl border border-[#E8EAF2] px-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-[#19213D]">{{ $integration['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ trans_choice('marketing.accounts_count', $integration['account_count'], ['count' => $integration['account_count']]) }}</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold',
                            'bg-emerald-50 text-emerald-700' => $integration['connected'],
                            'bg-slate-100 text-slate-600' => ! $integration['connected'],
                        ])>
                            {{ $integration['connected'] ? __('marketing.status_connected') : __('marketing.status_not_connected') }}
                        </span>
                    </li>
                @empty
                    <li class="text-sm text-slate-600">{{ __('marketing.empty_accounts_title') }}</li>
                @endforelse
            </ul>

            @if ($overview['connected_accounts'] === 0)
                <div class="mt-5 rounded-xl border border-dashed border-[#7138F5]/30 bg-[#F1EBFF]/50 p-4">
                    <p class="font-semibold text-[#3B1577]">{{ __('marketing.empty_accounts_title') }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ __('marketing.empty_accounts_body') }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-[#E8EAF2] bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-[#19213D]">{{ __('marketing.checklist_title') }}</h2>
            <ul class="mt-4 space-y-3">
                @foreach ($overview['checklist'] as $item)
                    @php
                        $label = __('marketing.checklist_'.$item['key']);
                    @endphp
                    <li class="flex items-start gap-3">
                        <span @class([
                            'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold',
                            'bg-emerald-100 text-emerald-700' => $item['done'],
                            'bg-slate-100 text-slate-500' => ! $item['done'],
                        ]) aria-hidden="true">
                            {{ $item['done'] ? '✓' : '·' }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-[#19213D]">{{ $label }}</p>
                            <p class="text-xs text-slate-500">{{ $item['done'] ? __('marketing.done') : __('marketing.pending') }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</div>
@endsection
