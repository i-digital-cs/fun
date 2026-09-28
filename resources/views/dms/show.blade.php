@extends('layouts.dashboard')

@section('title', __('friends.dm_title').' — '.$other->name)

@section('content')
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('friends.dm_title') }}</h1>
                <a href="{{ $other->publicProfileUrl() }}" class="mt-1 inline-flex items-center gap-2 text-sm font-medium text-indigo-600 no-underline hover:text-indigo-800">
                    @if ($other->profile_image_path)
                        <img src="{{ $other->profile_image_path }}" alt="" class="h-7 w-7 rounded-full object-cover">
                    @else
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-[10px] font-black text-white">{{ user_initials($other->name) }}</span>
                    @endif
                    {{ $other->name }}
                </a>
            </div>
            <a href="{{ route('chats.index', ['tab' => 'direct']) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:text-indigo-700">{{ __('chat.inbox_title') }}</a>
        </div>

        <div
            id="dm-chat"
            class="event-chat flex min-h-[28rem] flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm"
            data-conversation-id="{{ $conversation->id }}"
            data-channel="dms.{{ $conversation->id }}"
            data-user-id="{{ auth()->id() }}"
            data-csrf="{{ csrf_token() }}"
            data-post-url="{{ route('dms.store', $conversation) }}"
        >
            <div id="dm-messages" class="event-chat__messages fancy-scroll min-h-0 flex-1 overflow-y-auto p-4 sm:p-5" role="log" aria-live="polite">
                @forelse ($messages as $message)
                    @php
                        $mine = (int) $message->user_id === (int) auth()->id();
                        $user = $message->user;
                        $photo = $user?->profile_image_path;
                        $initials = user_initials($user?->name);
                        $timeLabel = $message->created_at?->timezone(config('app.timezone'))->format('g:i A');
                    @endphp
                    <div class="chat-row {{ $mine ? 'chat-row--mine' : 'chat-row--theirs' }}" data-message-id="{{ $message->id }}">
                        @unless ($mine)
                            <span class="chat-row__avatar" aria-hidden="true">
                                @if (filled($photo))
                                    <img src="{{ $photo }}" alt="" loading="lazy" />
                                @else
                                    <span>{{ $initials }}</span>
                                @endif
                            </span>
                        @endunless
                        <div class="chat-row__bubble">
                            <p class="chat-row__body whitespace-pre-wrap break-words">{{ $message->body }}</p>
                            <p class="chat-row__meta text-[10px] opacity-70">{{ $timeLabel }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-slate-500" id="dm-empty">{{ __('chat.empty_thread') }}</p>
                @endforelse
            </div>

            <form id="dm-form" method="POST" action="{{ route('dms.store', $conversation) }}" class="border-t border-slate-100 bg-slate-50/80 p-3 sm:p-4">
                @csrf
                <div class="flex gap-2">
                    <label class="sr-only" for="dm-body">{{ __('chat.placeholder') }}</label>
                    <input
                        id="dm-body"
                        name="body"
                        type="text"
                        maxlength="1000"
                        required
                        autocomplete="off"
                        placeholder="{{ __('chat.placeholder') }}"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none ring-indigo-500/30 focus:border-indigo-300 focus:ring-4"
                    >
                    <button type="submit" class="shrink-0 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('chat.send') }}</button>
                </div>
                @error('body')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/dm-chat.js'])
@endpush
