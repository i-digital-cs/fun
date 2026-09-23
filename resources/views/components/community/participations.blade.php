@props([
    'memberships',
    'pendingRequests',
    'allowDemo' => true,
])

@php
    $scheduledCount = $memberships->count() + $pendingRequests->count();
    $showDemo = $allowDemo && $scheduledCount === 0;
@endphp

<section class="mt-10">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/25">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                    <path stroke-linecap="round" d="M12 14v4M10 16h4"/>
                </svg>
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                {{ __('design.part_title') }}
            </h2>
            <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-bold text-indigo-700">
                {{ __('design.part_badge', ['count' => $showDemo ? 2 : $scheduledCount]) }}
            </span>
        </div>
        <a href="{{ route('events.mine') }}" class="group flex items-center gap-1 text-sm font-bold text-indigo-600 transition hover:text-indigo-800">
            {{ __('home.manage_all') }}
            <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        @if ($scheduledCount === 0 && ! $showDemo)
            <div class="col-span-full rounded-3xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center">
                <p class="text-sm font-medium text-slate-600">{{ __('home.participations_empty') }}</p>
                <a href="{{ route('events.index') }}" class="mt-4 inline-flex rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30">
                    {{ __('events.discover') }}
                </a>
            </div>
        @elseif ($showDemo)
            {{-- Demo confirmed card --}}
            <article class="group relative flex flex-col gap-4 overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-indigo-600/10">
                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-violet-500 to-orange-400"></span>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1.06 14.54l-3.48-3.48 1.41-1.41 2.07 2.07 4.24-4.24 1.41 1.41-5.65 5.65z"/>
                        </svg>
                        {{ __('design.card_confirmed') }}
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="flex items-center gap-1.5 rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white">
                            <svg class="h-3.5 w-3.5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" d="M12 7v5l3 2"/>
                            </svg>
                            {{ __('design.card_countdown') }}
                        </span>
                        <span class="text-xs font-semibold text-slate-400">Tonight · 9:00 PM</span>
                    </span>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
                        7-a-side Friendly Match &amp; Tactics
                    </h3>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                        <svg class="h-4 w-4 shrink-0 text-indigo-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                        </svg>
                        Pitch 4, Obour Sporting Club, District 2
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @foreach ([['label' => __('home.step_requested'), 'done' => true], ['label' => __('home.step_accepted'), 'done' => true], ['label' => __('home.step_gathering'), 'done' => false]] as $i => $step)
                        <div class="flex flex-1 items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <span @class([
                                    'flex h-7 w-7 items-center justify-center rounded-full',
                                    'bg-emerald-500 text-white shadow-md shadow-emerald-500/30' => $step['done'],
                                    'bg-slate-100 text-slate-400 ring-4 ring-slate-50' => ! $step['done'],
                                ])>
                                    @if ($step['done'])
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" d="M9 12l2 2 4-4"/>
                                            <circle cx="12" cy="12" r="9"/>
                                        </svg>
                                    @else
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                                        </svg>
                                    @endif
                                </span>
                                <span @class(['text-xs font-bold text-emerald-700' => $step['done'], 'text-xs font-semibold text-slate-400' => ! $step['done']])>{{ $step['label'] }}</span>
                            </div>
                            @if ($i < 2)
                                <span class="h-0.5 flex-1 rounded-full bg-gradient-to-r from-emerald-300 to-slate-200"></span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-black text-white shadow-md shadow-indigo-500/25">YM</span>
                        <div class="leading-tight">
                            <p class="text-sm font-bold text-slate-800">Youssef Mansour</p>
                            <p class="text-[11px] font-medium text-slate-400">{{ __('home.host') }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('events.index') }}" class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            {{ __('design.map') }}
                        </a>
                        <a href="{{ route('events.mine') }}" class="flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-indigo-600/35 active:translate-y-0">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                            </svg>
                            {{ __('home.open_chat') }}
                        </a>
                    </div>
                </div>
            </article>

            {{-- Demo pending card --}}
            <article class="group relative flex flex-col gap-4 overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-amber-500/10">
                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400"></span>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-bold text-amber-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 6v6l3 2"/>
                            <circle cx="12" cy="12" r="9"/>
                        </svg>
                        {{ __('home.request_pending') }}
                    </span>
                    <span class="text-xs font-semibold text-slate-400">Thursday · 7:30 PM</span>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
                        Community Civic Tech &amp; Urban Design Circle
                    </h3>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                        <svg class="h-4 w-4 shrink-0 text-indigo-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                        </svg>
                        Caribou Coffee terrace, Zone B
                    </p>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200/70 bg-amber-50/70 px-4 py-3">
                    <p class="text-xs font-medium leading-relaxed text-amber-800">{{ __('home.pending_review_notice') }}</p>
                    <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-amber-600 shadow-sm">#6 in queue</span>
                </div>
                <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-rose-400 to-orange-400 text-xs font-black text-white shadow-md shadow-orange-500/25">NS</span>
                        <div class="leading-tight">
                            <p class="text-sm font-bold text-slate-800">Nourhan Samir</p>
                            <p class="flex items-center gap-1 text-[11px] font-medium text-slate-400">
                                <svg class="h-3 w-3 fill-amber-400 text-amber-400" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                {{ __('home.host') }} · 4.9 ★
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-600">Cancel</span>
                        <a href="{{ route('events.index') }}" class="flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-slate-800 active:translate-y-0">
                            {{ __('events.view_event') }}
                            <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M5 12h14M13 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </article>
        @else
            @foreach ($memberships as $membership)
                @php($event = $membership->event)
                @continue(! $event)
                <article class="group relative flex flex-col gap-4 overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-indigo-600/10">
                    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-violet-500 to-orange-400"></span>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1.06 14.54l-3.48-3.48 1.41-1.41 2.07 2.07 4.24-4.24 1.41 1.41-5.65 5.65z"/>
                            </svg>
                            {{ __('design.card_confirmed') }}
                        </span>
                        <span class="text-xs font-semibold text-slate-400">
                            {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('l · g:i A') }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
                            <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                        </h3>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                            <svg class="h-4 w-4 shrink-0 text-indigo-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                            </svg>
                            {{ $event->venue }}@if($event->city) · {{ $event->city->localizedName() }}@endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @foreach ([['label' => __('home.step_requested'), 'done' => true], ['label' => __('home.step_accepted'), 'done' => true], ['label' => __('home.step_gathering'), 'done' => false]] as $i => $step)
                            <div class="flex flex-1 items-center gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span @class([
                                        'flex h-7 w-7 items-center justify-center rounded-full',
                                        'bg-emerald-500 text-white shadow-md shadow-emerald-500/30' => $step['done'],
                                        'bg-slate-100 text-slate-400 ring-4 ring-slate-50' => ! $step['done'],
                                    ])>
                                        @if ($step['done'])
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" d="M9 12l2 2 4-4"/>
                                                <circle cx="12" cy="12" r="9"/>
                                            </svg>
                                        @else
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <span @class(['text-xs font-bold text-emerald-700' => $step['done'], 'text-xs font-semibold text-slate-400' => ! $step['done']])>{{ $step['label'] }}</span>
                                </div>
                                @if ($i < 2)
                                    <span class="h-0.5 flex-1 rounded-full bg-gradient-to-r from-emerald-300 to-slate-200"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-black text-white shadow-md shadow-indigo-500/25">
                                {{ user_initials($event->creator?->name) }}
                            </span>
                            <div class="leading-tight">
                                <p class="text-sm font-bold text-slate-800">{{ $event->creator?->name }}</p>
                                <p class="text-[11px] font-medium text-slate-400">{{ __('home.host') }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('events.ticket', $event) }}" class="flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-3.5 py-2.5 text-xs font-extrabold text-white shadow-md shadow-orange-500/25 transition hover:brightness-105">
                                {{ __('events.view_ticket') }}
                            </a>
                            <a href="{{ route('events.chat', $event) }}" class="flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-indigo-600/35 active:translate-y-0">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                                </svg>
                                {{ __('home.open_chat') }}
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach

            @foreach ($pendingRequests as $joinRequest)
                @php($event = $joinRequest->event)
                @continue(! $event)
                <article class="group relative flex flex-col gap-4 overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-amber-500/10">
                    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400"></span>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-bold text-amber-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M12 6v6l3 2"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                            {{ __('home.request_pending') }}
                        </span>
                        <span class="text-xs font-semibold text-slate-400">
                            {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('l · g:i A') }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
                            <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                        </h3>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                            <svg class="h-4 w-4 shrink-0 text-indigo-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                            </svg>
                            {{ $event->venue }}@if($event->city) · {{ $event->city->localizedName() }}@endif
                        </p>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200/70 bg-amber-50/70 px-4 py-3">
                        <p class="text-xs font-medium leading-relaxed text-amber-800">{{ __('home.pending_review_notice') }}</p>
                    </div>
                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-rose-400 to-orange-400 text-xs font-black text-white shadow-md shadow-orange-500/25">
                                {{ user_initials($event->creator?->name) }}
                            </span>
                            <div class="leading-tight">
                                <p class="text-sm font-bold text-slate-800">{{ $event->creator?->name }}</p>
                                <p class="text-[11px] font-medium text-slate-400">{{ __('home.host') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('events.show', $event) }}" class="flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-slate-800 active:translate-y-0">
                            {{ __('events.view_event') }}
                            <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M5 12h14M13 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </article>
            @endforeach
        @endif
    </div>
</section>
