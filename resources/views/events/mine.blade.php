@extends('layouts.app')

@section('title', __('events.my_events'))

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('events.my_events') }}</h1>
        <a href="{{ route('events.create') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('events.create_event') }}</a>
    </div>

    @if ($events->isEmpty())
        <p class="text-muted">{{ __('events.empty') }}</p>
    @else
        <div class="space-y-3">
            @foreach ($events as $event)
                <a href="{{ route('events.show', $event) }}" class="block rounded-xl border border-border bg-surface p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold">{{ $event->title }}</h2>
                        <span class="text-xs text-muted">{{ $event->status->value }}</span>
                    </div>
                    <p class="mt-1 text-sm text-muted">{{ $event->members_count }} / {{ $event->max_participants }} · {{ $event->city?->localizedName() }}</p>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $events->links() }}</div>
    @endif
@endsection
