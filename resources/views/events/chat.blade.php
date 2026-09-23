@extends('layouts.dashboard')

@section('title', __('chat.title').' — '.$event->title)

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">
                    <a href="{{ route('events.show', $event) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $event->title }}</a>
                </p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('chat.title') }}</h1>
            </div>
            <a href="{{ route('events.show', $event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.view_event') }}</a>
        </div>

        <div
            id="event-chat"
            class="flex min-h-[28rem] flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm"
            data-event-id="{{ $event->id }}"
            data-post-url="{{ route('events.chat.store', $event) }}"
            data-channel="events.{{ $event->id }}.chat"
            data-user-id="{{ auth()->id() }}"
            data-csrf="{{ csrf_token() }}"
        >
            <div id="chat-messages" class="flex-1 space-y-3 overflow-y-auto p-5" role="log" aria-live="polite">
                @forelse ($messages as $message)
                    <div class="chat-message rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3" data-message-id="{{ $message->id }}">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $message->user?->name }}</p>
                            <div class="flex items-center gap-2">
                                @if ($message->user_id !== auth()->id())
                                    <a href="{{ route('reports.create', ['reportable_type' => 'message', 'reportable_id' => $message->id]) }}" class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('safety.report') }}</a>
                                @endif
                                <time class="text-xs text-slate-400" datetime="{{ $message->created_at?->toIso8601String() }}">
                                    {{ $message->created_at?->timezone(config('app.timezone'))->format('H:i') }}
                                </time>
                            </div>
                        </div>
                        <p class="mt-1 whitespace-pre-wrap break-words text-sm text-slate-600">{{ $message->body }}</p>
                    </div>
                @empty
                    <p id="chat-empty" class="text-sm text-slate-500">{{ __('chat.empty_thread') }}</p>
                @endforelse
            </div>

            <form id="chat-form" method="POST" action="{{ route('events.chat.store', $event) }}" class="border-t border-slate-200/70 bg-slate-50/50 p-5">
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
                        class="min-h-[2.75rem] flex-1 resize-y rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    ></textarea>
                    <button type="submit" class="self-end rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105">
                        {{ __('chat.send') }}
                    </button>
                </div>
                <p id="chat-status" class="mt-2 hidden text-xs text-slate-500" data-fallback="{{ __('chat.realtime_unavailable') }}"></p>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/event-chat.js'])
@endpush
