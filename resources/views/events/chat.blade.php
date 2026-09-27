@extends('layouts.dashboard')

@section('title', __('chat.title').' — '.$event->title)

@section('content')
    <div class="event-chat-page mx-auto flex w-full max-w-3xl flex-col gap-3">
        <div class="event-chat-page__header flex shrink-0 flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('chat.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    <a href="{{ event_show_url($event) }}" class="font-medium text-indigo-600 hover:text-indigo-800 bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</a>
                </p>
                @if ($event->creator)
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($event->creator->public_id)
                            <a href="{{ route('users.public.show', $event->creator->public_id) }}" class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50/80 py-1 pe-3 ps-1 no-underline transition hover:bg-indigo-100">
                                @if ($event->creator->profile_image_path)
                                    <img src="{{ $event->creator->profile_image_path }}" alt="" class="h-7 w-7 rounded-full object-cover">
                                @else
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-[10px] font-black text-white">{{ user_initials($event->creator->name) }}</span>
                                @endif
                                <span class="text-xs font-bold text-slate-800">{{ $event->creator->name }}</span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-indigo-700 shadow-sm">{{ __('home.host') }}</span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50/80 py-1 pe-3 ps-1">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-[10px] font-black text-white">{{ user_initials($event->creator->name) }}</span>
                                <span class="text-xs font-bold text-slate-800">{{ $event->creator->name }}</span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-indigo-700 shadow-sm">{{ __('home.host') }}</span>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            <a href="{{ event_show_url($event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.view_event') }}</a>
        </div>

        <div
            id="event-chat"
            class="event-chat flex min-h-0 flex-1 flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm"
            data-event-id="{{ $event->id }}"
            data-owner-id="{{ $event->created_by }}"
            data-host-label="{{ __('home.host') }}"
            data-post-url="{{ event_route('events.chat.store', $event) }}"
            data-channel="events.{{ $event->id }}.chat"
            data-user-id="{{ auth()->id() }}"
            data-csrf="{{ csrf_token() }}"
            data-report-url="{{ route('reports.create') }}"
            data-report-label="{{ __('safety.report') }}"
        >
            <div id="chat-messages" class="event-chat__messages fancy-scroll min-h-0 flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-5" role="log" aria-live="polite">
                @forelse ($messages as $message)
                    @php
                        $mine = (int) $message->user_id === (int) auth()->id();
                        $isSystem = (bool) ($message->is_system ?? false);
                        $isHost = (int) $message->user_id === (int) $event->created_by;
                        $user = $message->user;
                        $photo = $user?->profile_image_path;
                        $initials = user_initials($user?->name);
                        $profileUrl = filled($user?->public_id) ? route('users.public.show', $user->public_id) : null;
                        $timeLabel = $message->created_at?->timezone(config('app.timezone'))->format('g:i A');
                    @endphp

                    @if ($isSystem)
                        <div class="chat-row chat-row--system" data-message-id="{{ $message->id }}" data-mine="0" data-system="1">
                            <p class="chat-row__system-text">{{ $message->body }}</p>
                        </div>
                    @else
                        <div
                            class="chat-row {{ $mine ? 'chat-row--mine' : 'chat-row--theirs' }}"
                            data-message-id="{{ $message->id }}"
                            data-mine="{{ $mine ? '1' : '0' }}"
                            @if ($isHost) data-host="1" @endif
                        >
                            @unless ($mine)
                                @if ($profileUrl)
                                    <a href="{{ $profileUrl }}" class="chat-row__avatar" title="{{ $user?->name }}">
                                        @if (filled($photo))
                                            <img src="{{ $photo }}" alt="" loading="lazy" />
                                        @else
                                            <span>{{ $initials }}</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="chat-row__avatar" aria-hidden="true">
                                        @if (filled($photo))
                                            <img src="{{ $photo }}" alt="" loading="lazy" />
                                        @else
                                            <span>{{ $initials }}</span>
                                        @endif
                                    </span>
                                @endif
                            @endunless

                            <div class="chat-message {{ $mine ? 'chat-message--mine' : 'chat-message--theirs' }}{{ $isHost ? ' chat-message--host' : '' }}">
                                @unless ($mine)
                                    <div class="chat-message__author-row">
                                        @if ($profileUrl)
                                            <a href="{{ $profileUrl }}" class="chat-message__author">{{ $user?->name }}</a>
                                        @else
                                            <p class="chat-message__author">{{ $user?->name }}</p>
                                        @endif
                                        @if ($isHost)
                                            <span class="chat-message__host-badge">{{ __('home.host') }}</span>
                                        @endif
                                    </div>
                                @else
                                    @if ($isHost)
                                        <div class="chat-message__author-row chat-message__author-row--mine">
                                            <span class="chat-message__host-badge chat-message__host-badge--mine">{{ __('home.host') }}</span>
                                        </div>
                                    @endif
                                @endunless
                                <p class="chat-message__body">{{ $message->body }}</p>
                                <div class="chat-message__footer">
                                    @if (! $mine)
                                        <a href="{{ route('reports.create', ['reportable_type' => 'message', 'reportable_id' => $message->id]) }}" class="chat-message__report">{{ __('safety.report') }}</a>
                                    @endif
                                    <time class="chat-message__time" datetime="{{ $message->created_at?->toIso8601String() }}">{{ $timeLabel }}</time>
                                </div>
                            </div>

                            @if ($mine)
                                @if ($profileUrl)
                                    <a href="{{ $profileUrl }}" class="chat-row__avatar" title="{{ $user?->name }}">
                                        @if (filled($photo))
                                            <img src="{{ $photo }}" alt="" loading="lazy" />
                                        @else
                                            <span>{{ $initials }}</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="chat-row__avatar" aria-hidden="true">
                                        @if (filled($photo))
                                            <img src="{{ $photo }}" alt="" loading="lazy" />
                                        @else
                                            <span>{{ $initials }}</span>
                                        @endif
                                    </span>
                                @endif
                            @endif
                        </div>
                    @endif
                @empty
                    <p id="chat-empty" class="py-8 text-center text-sm text-slate-500">{{ __('chat.empty_thread') }}</p>
                @endforelse
            </div>

            {{-- Uiverse.io by dorian_8749 — chat composer --}}
            <form id="chat-form" method="POST" action="{{ event_route('events.chat.store', $event) }}" class="event-chat__composer shrink-0">
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
