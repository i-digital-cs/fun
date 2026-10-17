@extends('layouts.dashboard')

@section('content')
    <div class="mb-6 max-w-3xl">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $landingTitle }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $landingDescription }}</p>
    </div>

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
@endsection
