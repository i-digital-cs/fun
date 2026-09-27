@extends('layouts.dashboard')

@section('title', __('events.my_events'))

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.my_events') }}</h1>
        <a href="{{ route('events.create') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">{{ __('events.create_event') }}</a>
    </div>

    @if ($pendingRequests->isNotEmpty())
        <section class="mb-7" aria-labelledby="pending-requests-heading">
            <h2 id="pending-requests-heading" class="mb-3 text-base font-extrabold tracking-tight text-slate-900 sm:text-lg">
                {{ __('events.pending_requests_section') }}
            </h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($pendingRequests as $joinRequest)
                    @continue(! $joinRequest->event)
                    @php
                        $event = $joinRequest->event;
                    @endphp
                    <article class="group relative flex flex-col gap-2.5 overflow-hidden rounded-2xl border border-amber-200/80 bg-amber-50/40 p-3.5 shadow-sm transition hover:shadow-lg hover:shadow-amber-500/10">
                        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400"></span>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                {{ __('home.request_pending') }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400">
                                {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A') }}
                            </span>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold leading-snug tracking-tight text-slate-900">
                                <a href="{{ event_show_url($event) }}" class="hover:text-indigo-700 bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</a>
                            </h3>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                                @if ($event->city)
                                    <span>{{ $event->city->localizedName() }}</span>
                                @endif
                                <span>{{ $event->members_count }} / {{ $event->max_participants }}</span>
                            </p>
                            <p class="mt-2 text-[10px] font-medium text-amber-800">{{ __('home.pending_review_notice') }}</p>
                        </div>
                        <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-amber-100/80 pt-2.5">
                            <a href="{{ event_show_url($event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-2.5 py-1.5 text-[10px] font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.view_event') }}</a>
                            <form method="POST" action="{{ event_route('events.join.cancel', $event) }}" class="cancel-btn-slot py-0" data-confirm="{{ __('ui.confirm_cancel_join') }}">
                                @csrf
                                <x-ui.cancel-btn type="submit" size="sm" :label="__('events.cancel_join')" />
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mb-7" aria-labelledby="hosting-events-heading">
        <h2 id="hosting-events-heading" class="mb-3 text-base font-extrabold tracking-tight text-slate-900 sm:text-lg">
            {{ __('events.hosting_events_section') }}
        </h2>

        @if ($hosting->isEmpty())
            <div class="rounded-2xl border border-slate-200/70 bg-white p-6 text-center shadow-sm">
                <p class="text-sm text-slate-500">{{ __('events.hosting_empty') }}</p>
                <a href="{{ route('events.create') }}" class="mt-3 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105">
                    {{ __('events.create_event') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($hosting as $event)
                    <article class="group relative flex flex-col gap-2.5 overflow-hidden rounded-2xl border border-indigo-100 bg-white p-3.5 shadow-sm transition hover:shadow-lg hover:shadow-indigo-600/10">
                        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-violet-500 to-orange-400"></span>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700">{{ __('events.your_own') }}</span>
                            <span class="rounded-full border border-slate-200/70 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">{{ $event->status->value }}</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
                                <a href="{{ event_show_url($event) }}" class="bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</a>
                            </h3>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                                <span class="inline-flex items-center gap-1 font-medium text-indigo-600">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                        <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                                    </svg>
                                    {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A') }}
                                </span>
                                <span>{{ $event->members_count }} / {{ $event->max_participants }}</span>
                                @if ($event->city)
                                    <span>{{ $event->city->localizedName() }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="mt-auto flex flex-wrap gap-2 border-t border-slate-100 pt-2.5">
                            <a href="{{ event_route('events.ticket', $event) }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-2.5 py-1.5 text-[10px] font-extrabold text-white shadow-md shadow-orange-500/25 transition hover:brightness-105">{{ __('events.view_ticket') }}</a>
                            <a href="{{ event_show_url($event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-2.5 py-1.5 text-[10px] font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.view_event') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-5">{{ $hosting->withQueryString()->links() }}</div>
        @endif
    </section>

    <section aria-labelledby="joined-events-heading">
        <h2 id="joined-events-heading" class="mb-3 text-base font-extrabold tracking-tight text-slate-900 sm:text-lg">
            {{ __('events.joined_events_section') }}
        </h2>

        @if ($joined->isEmpty())
            <div class="rounded-2xl border border-slate-200/70 bg-white p-6 text-center shadow-sm">
                <p class="text-sm text-slate-500">{{ __('events.joined_empty') }}</p>
                <a href="{{ route('events.index') }}" class="mt-3 inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100">
                    {{ __('events.discover') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($joined as $event)
                    <article class="group relative flex flex-col gap-2.5 overflow-hidden rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-sm transition hover:shadow-lg hover:shadow-emerald-600/10">
                        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-400 via-teal-400 to-indigo-400"></span>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">{{ __('events.joined') }}</span>
                            <span class="rounded-full border border-slate-200/70 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">{{ $event->status->value }}</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
                                <a href="{{ event_show_url($event) }}" class="bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</a>
                            </h3>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                                <span class="inline-flex items-center gap-1 font-medium text-indigo-600">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                        <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                                    </svg>
                                    {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A') }}
                                </span>
                                <span>{{ $event->members_count }} / {{ $event->max_participants }}</span>
                                @if ($event->city)
                                    <span>{{ $event->city->localizedName() }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="mt-auto flex flex-wrap gap-2 border-t border-slate-100 pt-2.5">
                            <a href="{{ event_route('events.ticket', $event) }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-2.5 py-1.5 text-[10px] font-extrabold text-white shadow-md shadow-orange-500/25 transition hover:brightness-105">{{ __('events.view_ticket') }}</a>
                            <a href="{{ event_show_url($event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-2.5 py-1.5 text-[10px] font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.view_event') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-5">{{ $joined->withQueryString()->links() }}</div>
        @endif
    </section>
@endsection
