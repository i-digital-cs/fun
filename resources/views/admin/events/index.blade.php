@extends('layouts.admin')

@section('title', __('admin.events'))

@section('content')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('admin.events') }}</h1>
        <form method="GET" class="flex gap-2">
            <select name="status" class="rounded-lg border border-border bg-surface px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach (['draft', 'published', 'cancelled', 'completed'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white">{{ __('admin.filter') }}</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse ($events as $event)
            <div class="rounded-xl border border-border bg-surface p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('events.show', $event) }}" class="font-semibold text-primary hover:underline">{{ $event->title }}</a>
                        <p class="mt-1 text-sm text-muted">
                            {{ $event->status->value }} · {{ $event->members_count }}/{{ $event->max_participants }}
                            · {{ $event->creator?->name }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.chat.index', $event) }}" class="rounded-lg border border-border px-3 py-1.5 text-sm">{{ __('admin.moderate_chat') }}</a>
                        @if (in_array($event->status->value, ['draft', 'published'], true))
                            <form method="POST" action="{{ route('admin.events.cancel', $event) }}">
                                @csrf
                                <button class="rounded-lg bg-danger px-3 py-1.5 text-sm font-medium text-white">{{ __('admin.cancel_event') }}</button>
                            </form>
                        @endif
                        @if ($event->status->value === 'published')
                            <form method="POST" action="{{ route('admin.events.complete', $event) }}">
                                @csrf
                                <button class="rounded-lg bg-success px-3 py-1.5 text-sm font-medium text-white">{{ __('admin.complete_event') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">{{ __('events.empty') }}</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
@endsection
