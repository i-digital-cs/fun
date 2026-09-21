@extends('layouts.app')

@section('title', $event->title)

@section('content')
    <article class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-2xl border border-border bg-surface p-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary">
                {{ $event->category?->localizedName() }}
                @if ($event->city)
                    <span class="font-medium text-muted">· {{ $event->city->localizedName() }}</span>
                @endif
            </p>
            <h1 class="mt-2 text-3xl font-semibold">{{ $event->title }}</h1>

            @if ($event->imageUrl())
                <img src="{{ $event->imageUrl() }}" alt="" class="mt-4 max-h-80 w-full rounded-xl object-cover">
            @endif

            <p class="mt-4 text-sm text-muted">
                {{ $event->starts_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                @if ($event->ends_at)
                    — {{ $event->ends_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                @endif
            </p>
            <p class="mt-1 text-sm text-muted">{{ $event->city?->localizedName() }}</p>
            <p class="mt-1 text-sm font-medium">{{ $event->venue }}</p>

            @if ($event->description)
                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed">{{ $event->description }}</p>
            @endif

            @if ($event->requirements)
                <div class="mt-4">
                    <h2 class="font-semibold">{{ __('events.requirements') }}</h2>
                    <p class="mt-1 whitespace-pre-line text-sm text-muted">{{ $event->requirements }}</p>
                </div>
            @endif

            @if ($event->expected_cost || $event->cost_per_person !== null)
                <div class="mt-4 space-y-1 text-sm">
                    @if ($event->expected_cost)
                        <p>{{ __('events.expected_cost') }}: {{ $event->expected_cost }}</p>
                    @endif
                    @if ($event->cost_per_person !== null)
                        <p>{{ __('events.cost_per_person_amount', ['amount' => $event->cost_per_person]) }}</p>
                    @endif
                </div>
            @endif

            <p class="mt-4 text-sm font-medium">
                {{ $event->members_count }} / {{ $event->max_participants }} {{ __('events.members') }}
            </p>
            <p class="mt-1 text-xs uppercase tracking-wide text-muted">{{ __('events.status') }}: {{ $event->status->value }}</p>

            <div class="mt-6 flex flex-wrap gap-2">
                @auth
                    @if ($joinAction->value === 'request_to_join' || $joinAction->value === 'request_rejected')
                        <form method="POST" action="{{ route('events.join', $event) }}">
                            @csrf
                            <button class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('events.request_to_join') }}</button>
                        </form>
                        @if ($joinAction->value === 'request_rejected')
                            <span class="rounded-lg border border-warning/40 px-4 py-2 text-sm font-medium text-warning">{{ __('events.request_rejected') }}</span>
                        @endif
                    @elseif ($joinAction->value === 'request_pending')
                        <span class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted">{{ __('events.request_pending') }}</span>
                    @elseif ($joinAction->value === 'joined')
                        <span class="rounded-lg bg-success/10 px-4 py-2 text-sm font-medium text-success">{{ __('events.joined') }}</span>
                        <a href="{{ route('events.chat', $event) }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">{{ __('chat.open_chat') }}</a>
                        <a href="{{ route('events.members', $event) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium">{{ __('events.members') }}</a>
                        @unless($event->isOwnedBy(auth()->user()))
                            <form method="POST" action="{{ route('events.leave', $event) }}">
                                @csrf
                                <button class="rounded-lg border border-danger/30 px-4 py-2 text-sm font-medium text-danger">{{ __('events.leave_event') }}</button>
                            </form>
                        @endunless
                    @elseif ($joinAction->value === 'event_full')
                        <span class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted">{{ __('events.event_full') }}</span>
                    @endif

                    @can('reviewJoinRequests', $event)
                        <a href="{{ route('events.requests', $event) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium">{{ __('events.join_requests') }}</a>
                        <a href="{{ route('events.members', $event) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium">{{ __('events.manage_members') }}</a>
                    @endcan

                    @can('update', $event)
                        <a href="{{ route('events.edit', $event) }}" class="rounded-lg border border-border px-3 py-2 text-sm font-medium">{{ __('events.edit_event') }}</a>
                        @if ($event->status->value === 'draft')
                            <form method="POST" action="{{ route('events.publish', $event) }}">@csrf<button class="rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-white">{{ __('events.publish') }}</button></form>
                        @endif
                        @if (in_array($event->status->value, ['draft', 'published'], true))
                            <form method="POST" action="{{ route('events.cancel', $event) }}">@csrf<button class="rounded-lg bg-danger px-3 py-2 text-sm font-semibold text-white">{{ __('events.cancel') }}</button></form>
                        @endif
                        @if ($event->status->value === 'published')
                            <form method="POST" action="{{ route('events.complete', $event) }}">@csrf<button class="rounded-lg bg-success px-3 py-2 text-sm font-semibold text-white">{{ __('events.complete') }}</button></form>
                        @endif
                    @endcan
                @else
                    @if ($joinAction->value === 'request_to_join')
                        <a href="{{ route('login') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('events.request_to_join') }}</a>
                    @elseif ($joinAction->value === 'event_full')
                        <span class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted">{{ __('events.event_full') }}</span>
                    @endif
                @endauth
            </div>
        </div>
    </article>
@endsection
