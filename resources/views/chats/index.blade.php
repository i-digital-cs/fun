@extends('layouts.dashboard')

@section('title', __('chat.inbox_title'))

@section('content')
    <div class="mx-auto max-w-3xl" id="chat-inbox" data-chat-inbox>
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('chat.inbox_title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('chat.inbox_subtitle') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($unreadTotal > 0)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">
                        {{ trans_choice('chat.unread_count', $unreadTotal, ['count' => $unreadTotal]) }}
                    </span>
                @endif
                @if ($chats->isNotEmpty())
                    <button
                        type="button"
                        data-chat-search-toggle
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700"
                        aria-expanded="false"
                        aria-controls="chat-inbox-search"
                        title="{{ __('chat.inbox_search') }}"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                        </svg>
                        <span class="sr-only">{{ __('chat.inbox_search') }}</span>
                    </button>
                @endif
            </div>
        </div>

        @if ($chats->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                <p class="text-base font-bold text-slate-800">{{ __('chat.inbox_empty_title') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ __('chat.inbox_empty_body') }}</p>
                <a href="{{ route('events.index') }}"
                   class="mt-5 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    {{ __('dashboard.nav_discover') }}
                </a>
            </div>
        @else
            <div class="mb-4 flex rounded-2xl bg-indigo-50/80 p-1 ring-1 ring-indigo-100" role="tablist" aria-label="{{ __('chat.inbox_filter') }}">
                <button
                    type="button"
                    role="tab"
                    data-chat-filter="recent"
                    aria-selected="true"
                    class="flex-1 rounded-xl bg-white px-3 py-2 text-sm font-bold text-indigo-700 shadow-sm transition"
                >{{ __('chat.inbox_recent') }}</button>
                <button
                    type="button"
                    role="tab"
                    data-chat-filter="unread"
                    aria-selected="false"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-slate-600 transition hover:text-slate-900"
                >
                    <span>{{ __('chat.inbox_unread') }}</span>
                    @if ($unreadTotal > 0)
                        <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadTotal > 99 ? '99+' : $unreadTotal }}</span>
                    @endif
                </button>
            </div>

            <div class="mb-4 hidden" data-chat-search-panel>
                <label class="sr-only" for="chat-inbox-search">{{ __('chat.inbox_search') }}</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                    </svg>
                    <input
                        id="chat-inbox-search"
                        type="search"
                        data-chat-search-input
                        placeholder="{{ __('chat.inbox_search') }}"
                        class="w-full rounded-2xl border border-slate-200 bg-white py-2.5 pe-4 ps-10 text-sm text-slate-800 shadow-sm outline-none ring-indigo-500/30 placeholder:text-slate-400 focus:border-indigo-300 focus:ring-4"
                    />
                </div>
            </div>

            <ul class="flex flex-col gap-1.5" role="list" data-chat-list>
                @foreach ($chats as $chat)
                    @php
                        $last = $chat['last_message'] ?? null;
                        $unread = (int) ($chat['unread_count'] ?? 0);
                        $preview = $last['body'] ?? __('chat.inbox_no_messages');
                        $who = $last
                            ? (($last['is_mine'] ?? false) ? __('chat.you').': ' : (($last['user']['name'] ?? '').': '))
                            : '';
                        $image = $chat['image_url'] ?? null;
                        $startsAt = $chat['starts_at'] ?? null;
                        $searchBlob = mb_strtolower(($chat['title'] ?? '').' '.($chat['city'] ?? '').' '.$preview.' '.($last['user']['name'] ?? ''));
                        $relative = isset($last['created_at'])
                            ? chat_relative_time(\Illuminate\Support\Carbon::parse($last['created_at']))
                            : '';
                    @endphp
                    <li
                        data-chat-row
                        data-unread="{{ $unread > 0 ? '1' : '0' }}"
                        data-search="{{ $searchBlob }}"
                    >
                        <a href="{{ $chat['chat_url'] }}"
                           @class([
                               'flex items-start gap-3 rounded-2xl px-3 py-3 no-underline transition sm:px-3.5',
                               'bg-indigo-50/70 ring-1 ring-indigo-100/80 hover:bg-indigo-50' => $unread > 0,
                               'bg-white ring-1 ring-slate-200/80 hover:bg-slate-50' => $unread === 0,
                           ])>
                            <span class="relative mt-0.5 h-12 w-12 shrink-0 overflow-hidden rounded-2xl bg-slate-100">
                                @if (filled($image))
                                    <img src="{{ $image }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                                @else
                                    <span @class([
                                        'flex h-full w-full items-center justify-center text-sm font-extrabold',
                                        'bg-indigo-600 text-white' => $unread > 0,
                                        'bg-slate-100 text-slate-600' => $unread === 0,
                                    ])>
                                        {{ mb_strtoupper(mb_substr($chat['title'], 0, 1)) }}
                                    </span>
                                @endif
                                @if ($unread > 0)
                                    <span class="absolute bottom-0 end-0 h-3.5 w-3.5 rounded-full border-2 border-white bg-indigo-600"></span>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span @class([
                                        'truncate text-[15px] text-slate-900',
                                        'font-extrabold' => $unread > 0,
                                        'font-semibold' => $unread === 0,
                                    ])>{{ $chat['title'] }}</span>
                                    @if ($relative !== '')
                                        <time @class([
                                            'shrink-0 text-[11px] font-bold',
                                            'text-indigo-600' => $unread > 0,
                                            'text-slate-400' => $unread === 0,
                                        ])>{{ $relative }}</time>
                                    @endif
                                </span>
                                @if (($chat['city'] ?? null) || $startsAt)
                                    <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-400">
                                        @if ($startsAt)
                                            {{ \Illuminate\Support\Carbon::parse($startsAt)->timezone(config('app.timezone'))->translatedFormat('M j · g:i A') }}
                                        @endif
                                        @if (($chat['city'] ?? null) && $startsAt) · @endif
                                        {{ $chat['city'] ?? '' }}
                                    </span>
                                @endif
                                <span class="mt-1 flex items-center gap-2">
                                    <span @class([
                                        'min-w-0 flex-1 truncate text-sm',
                                        'font-semibold text-slate-800' => $unread > 0,
                                        'text-slate-500' => $unread === 0,
                                    ])>{{ $who }}{{ $preview }}</span>
                                    @if ($unread > 0)
                                        <span class="inline-flex min-w-[1.4rem] shrink-0 items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-[11px] font-bold text-white">
                                            {{ $unread > 99 ? '99+' : $unread }}
                                        </span>
                                    @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <p data-chat-empty-unread class="mt-8 hidden text-center text-sm text-slate-500">
                {{ __('chat.inbox_no_unread') }}
            </p>
            <p data-chat-empty-search class="mt-8 hidden text-center text-sm text-slate-500">
                {{ __('chat.inbox_no_results') }}
            </p>
        @endif
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/chat-inbox.js'])
@endpush
