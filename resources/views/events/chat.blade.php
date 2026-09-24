@extends('layouts.dashboard')

@section('title', __('chat.title').' — '.$event->title)

@section('content')
    <div class="mx-auto flex max-w-3xl flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">
                    <a href="{{ event_show_url($event) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $event->title }}</a>
                </p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('chat.title') }}</h1>
            </div>
            <a href="{{ event_show_url($event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.view_event') }}</a>
        </div>

        <div
            id="event-chat"
            class="event-chat flex min-h-[28rem] flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm"
            data-event-id="{{ $event->id }}"
            data-post-url="{{ route('events.chat.store', $event) }}"
            data-channel="events.{{ $event->id }}.chat"
            data-user-id="{{ auth()->id() }}"
            data-csrf="{{ csrf_token() }}"
        >
            <div id="chat-messages" class="event-chat__messages fancy-scroll flex-1 space-y-3 overflow-y-auto p-5" role="log" aria-live="polite">
                @forelse ($messages as $message)
                    @php($mine = (int) $message->user_id === (int) auth()->id())
                    <div
                        class="chat-message {{ $mine ? 'chat-message--mine' : 'chat-message--theirs' }}"
                        data-message-id="{{ $message->id }}"
                        data-mine="{{ $mine ? '1' : '0' }}"
                    >
                        <div class="chat-message__meta">
                            <p class="chat-message__author">{{ $message->user?->name }}</p>
                            <div class="chat-message__meta-end">
                                @if (! $mine)
                                    <a href="{{ route('reports.create', ['reportable_type' => 'message', 'reportable_id' => $message->id]) }}" class="chat-message__report">{{ __('safety.report') }}</a>
                                @endif
                                <time class="chat-message__time" datetime="{{ $message->created_at?->toIso8601String() }}">
                                    {{ $message->created_at?->timezone(config('app.timezone'))->format('H:i') }}
                                </time>
                            </div>
                        </div>
                        <p class="chat-message__body">{{ $message->body }}</p>
                    </div>
                @empty
                    <p id="chat-empty" class="text-center text-sm text-slate-500">{{ __('chat.empty_thread') }}</p>
                @endforelse
            </div>

            {{-- Uiverse.io by dorian_8749 — chat composer --}}
            <form id="chat-form" method="POST" action="{{ route('events.chat.store', $event) }}" class="event-chat__composer">
                @csrf
                <div class="chat-composer">
                    <div class="chat-composer__shell">
                        <label for="chat-body" class="sr-only">{{ __('chat.placeholder') }}</label>
                        <textarea
                            id="chat-body"
                            name="body"
                            rows="1"
                            maxlength="1000"
                            required
                            placeholder="{{ __('chat.placeholder') }}"
                            class="chat-composer__input"
                            data-chat-input
                        ></textarea>

                        <div class="chat-composer__toolbar">
                            <div class="chat-composer__tools">
                                <button
                                    type="button"
                                    class="chat-composer__icon-btn"
                                    data-chat-attach
                                    aria-label="{{ __('chat.attach') }}"
                                    title="{{ __('chat.attach_soon') }}"
                                >
                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="16" width="16" aria-hidden="true">
                                        <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                    </svg>
                                </button>

                                <span class="chat-composer__chip" aria-hidden="true">
                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="16" width="16">
                                        <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                                    </svg>
                                    <span>{{ __('chat.chip') }}</span>
                                </span>
                            </div>

                            <button
                                type="submit"
                                class="chat-composer__send"
                                aria-label="{{ __('chat.send') }}"
                                data-chat-send
                            >
                                <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="16" width="16" aria-hidden="true">
                                    <path d="m22 2-7 20-4-9-9-4Z"></path>
                                    <path d="M22 2 11 13"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <p id="chat-status" class="event-chat__status hidden" data-fallback="{{ __('chat.realtime_unavailable') }}"></p>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/event-chat.js'])
@endpush
