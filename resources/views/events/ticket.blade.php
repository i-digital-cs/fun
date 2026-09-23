@extends('layouts.dashboard')

@section('title', __('events.ticket_page_title', ['title' => $ticket['title']]))

@section('content')
    <div class="mx-auto max-w-lg space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-indigo-600">{{ __('events.ticket_word') }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $ticket['title'] }}</h1>
            </div>
            <a href="{{ route('events.show', $event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
                {{ __('events.view_event') }}
            </a>
        </div>

        <div class="flex justify-center py-4">
            <x-events.ticket :ticket="$ticket" />
        </div>

        <p class="text-center text-sm text-slate-500">{{ __('events.ticket_help') }}</p>
    </div>
@endsection
