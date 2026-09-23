@extends('layouts.dashboard')

@section('title', __('events.favorites'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.favorites') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('events.favorites_hint') }}</p>
        </div>
        <a href="{{ route('events.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
            {{ __('events.discover') }}
        </a>
    </div>

    @if ($events->isEmpty())
        <div class="rounded-3xl border border-slate-200/70 bg-white p-8 text-center shadow-sm">
            <p class="text-slate-500">{{ __('events.favorites_empty') }}</p>
            <a href="{{ route('events.index') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                {{ __('events.discover') }}
            </a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
                <x-events.card
                    :event="$event"
                    :join-action="$joinActionsById[$event->id] ?? null"
                    :favorited="isset($favoritedIds[$event->id])"
                />
            @endforeach
        </div>
        <div class="mt-6">{{ $events->links() }}</div>
    @endif
@endsection
