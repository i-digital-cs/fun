@extends('layouts.dashboard')

@section('title', __('events.my_events'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.my_events') }}</h1>
        <a href="{{ route('events.create') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">{{ __('events.create_event') }}</a>
    </div>

    @if ($events->isEmpty())
        <div class="rounded-3xl border border-slate-200/70 bg-white p-8 text-center shadow-sm">
            <p class="text-slate-500">{{ __('events.empty') }}</p>
            <a href="{{ route('events.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                {{ __('events.create_event') }}
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($events as $event)
                <div class="rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm transition hover:shadow-lg hover:shadow-indigo-600/10">
                    <a href="{{ route('events.show', $event) }}" class="group block">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h2 class="font-extrabold tracking-tight text-slate-900 transition group-hover:text-indigo-700">{{ $event->title }}</h2>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                                    <span class="inline-flex items-center gap-1 font-medium text-indigo-600">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
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
                            <span class="shrink-0 rounded-full border border-slate-200/70 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $event->status->value }}</span>
                        </div>
                    </a>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('events.ticket', $event) }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-1.5 text-xs font-extrabold text-white shadow-md shadow-orange-500/25 transition hover:brightness-105">{{ __('events.view_ticket') }}</a>
                        <a href="{{ route('events.show', $event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.view_event') }}</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $events->links() }}</div>
    @endif
@endsection
