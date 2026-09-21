@extends('layouts.app')

@section('title', __('chat.title').' — '.$event->title)

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-muted">
                    <a href="{{ route('events.show', $event) }}" class="text-primary hover:underline">{{ $event->title }}</a>
                </p>
                <h1 class="text-2xl font-semibold">{{ __('chat.title') }}</h1>
            </div>
            <a href="{{ route('events.show', $event) }}" class="rounded-lg border border-border px-3 py-2 text-sm font-medium">{{ __('events.view_event') }}</a>
        </div>

        <div
            id="event-chat"
            class="flex min-h-[28rem] flex-col overflow-hidden rounded-2xl border border-border bg-surface"
            data-event-id="{{ $event->id }}"
            data-post-url="{{ route('events.chat.store', $event) }}"
            data-channel="events.{{ $event->id }}.chat"
            data-user-id="{{ auth()->id() }}"
            data-csrf="{{ csrf_token() }}"
        >
            <div id="chat-messages" class="flex-1 space-y-3 overflow-y-auto p-4" role="log" aria-live="polite">
                @forelse ($messages as $message)
                    <div class="chat-message rounded-xl bg-background px-3 py-2" data-message-id="{{ $message->id }}">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold">{{ $message->user?->name }}</p>
                            <div class="flex items-center gap-2">
                                @if ($message->user_id !== auth()->id())
                                    <a href="{{ route('reports.create', ['reportable_type' => 'message', 'reportable_id' => $message->id]) }}" class="text-xs text-danger hover:underline">{{ __('safety.report') }}</a>
                                @endif
                                <time class="text-xs text-muted" datetime="{{ $message->created_at?->toIso8601String() }}">
                                    {{ $message->created_at?->timezone(config('app.timezone'))->format('H:i') }}
                                </time>
                            </div>
                        </div>
                        <p class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $message->body }}</p>
                    </div>
                @empty
                    <p id="chat-empty" class="text-sm text-muted">{{ __('chat.empty_thread') }}</p>
                @endforelse
            </div>

            <form id="chat-form" method="POST" action="{{ route('events.chat.store', $event) }}" class="border-t border-border p-4">
                @csrf
                <label for="chat-body" class="sr-only">{{ __('chat.placeholder') }}</label>
                <div class="flex gap-2">
                    <textarea
                        id="chat-body"
                        name="body"
                        rows="2"
                        maxlength="1000"
                        required
                        placeholder="{{ __('chat.placeholder') }}"
                        class="min-h-[2.75rem] flex-1 resize-y rounded-lg border border-border bg-background px-3 py-2 text-sm"
                    ></textarea>
                    <button type="submit" class="self-end rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">
                        {{ __('chat.send') }}
                    </button>
                </div>
                <p id="chat-status" class="mt-2 hidden text-xs text-muted" data-fallback="{{ __('chat.realtime_unavailable') }}"></p>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/event-chat.js'])
@endpush
