@extends('layouts.dashboard')

@section('title', __('events.ticket_page_title', ['title' => $ticket['title']]))

@section('content')
    <div class="event-ticket-stage relative mx-auto max-w-3xl overflow-hidden rounded-[2rem] border border-white/10 px-4 py-10 sm:px-8 sm:py-14">
        <div class="event-ticket-stage__glow" aria-hidden="true"></div>
        <div class="event-ticket-stage__noise bg-noise" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-200/90">{{ __('events.ticket_word') }}</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-white sm:text-2xl">{{ $ticket['title'] }}</h1>
                <p class="mt-1 text-sm text-slate-300">{{ $ticket['date_label'] }} · {{ $ticket['venue_line'] }}</p>
            </div>
            <a href="{{ route('events.show', $event) }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                {{ __('events.view_event') }}
            </a>
        </div>

        <div class="relative z-10 mt-10 flex justify-center py-2">
            <x-events.ticket :ticket="$ticket" class="event-ticket--stage" />
        </div>

        <p class="relative z-10 mx-auto mt-8 max-w-md text-center text-sm leading-relaxed text-slate-300">
            {{ __('events.ticket_help') }}
        </p>
    </div>
@endsection
