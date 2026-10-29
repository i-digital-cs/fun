@extends('layouts.dashboard')

@section('title', __('chat.inbox_title'))

@section('content')
    @php
        $selectedKey = $selectedKey ?? '';
        $typeFilter = $typeFilter ?? 'all';
        $items = $items ?? collect();
    @endphp

    <div class="chat-v2 flex min-h-0 flex-1 flex-col overflow-hidden bg-[#F8F9FE] lg:min-h-[28rem] lg:rounded-t-2xl"
         data-chat-v2
         data-selected="{{ $selectedKey }}">
        <div class="flex min-h-0 flex-1 overflow-hidden border-t border-slate-200/80 bg-white shadow-sm lg:border lg:border-slate-200/80 lg:rounded-t-2xl">
            {{-- Left: conversation list --}}
            <aside @class([
                'flex w-full shrink-0 flex-col border-e border-slate-200/80 bg-white lg:w-[34%] lg:max-w-md',
                'hidden lg:flex' => $selectedKey !== '',
                'flex' => $selectedKey === '',
            ]) data-chat-v2-list>
                <header class="shrink-0 border-b border-slate-100 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top,0px))] sm:py-3.5">
                    <h1 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-lg">{{ __('chat.inbox_title') }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500 sm:text-xs">{{ __('chat.inbox_subtitle_v2') }}</p>
                    <label class="sr-only" for="chat-v2-search">{{ __('chat.inbox_search') }}</label>
                    <div class="relative mt-3">
                        <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                        </svg>
                        <input id="chat-v2-search" type="search" data-chat-v2-search
                               placeholder="{{ __('chat.inbox_search') }}"
                               class="w-full rounded-xl border border-slate-200 bg-[#F8F9FE] py-2 pe-3 ps-9 text-sm outline-none ring-[#7138F5]/25 placeholder:text-slate-400 focus:border-[#7138F5] focus:ring-4"/>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5" role="tablist" aria-label="{{ __('chat.inbox_filter') }}">
                        @foreach ([
                            'all' => __('chat.filter_all'),
                            'unread' => __('chat.inbox_unread'),
                            'direct' => __('friends.inbox_direct'),
                            'events' => __('friends.inbox_events'),
                        ] as $key => $label)
                            <button type="button"
                                    data-chat-v2-filter="{{ $key }}"
                                    aria-selected="{{ $typeFilter === $key ? 'true' : 'false' }}"
                                    @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-bold transition',
                                        'bg-[#7138F5] text-white' => $typeFilter === $key,
                                        'bg-slate-100 text-slate-600 hover:bg-slate-200' => $typeFilter !== $key,
                                    ])>{{ $label }}</button>
                        @endforeach
                    </div>
                </header>

                <div class="fancy-scroll min-h-0 flex-1 overflow-y-auto" data-chat-v2-rows>
                    @if ($items->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <p class="text-sm font-bold text-slate-800">{{ __('chat.inbox_empty_title') }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ __('chat.inbox_empty_body') }}</p>
                            <a href="{{ route('events.index') }}" class="mt-4 inline-flex rounded-xl bg-[#7138F5] px-4 py-2 text-xs font-bold text-white hover:bg-[#5825D6]">{{ __('dashboard.nav_discover') }}</a>
                        </div>
                    @else
                        <ul class="flex flex-col" role="list">
                            @foreach ($items as $item)
                                @php
                                    $unread = (int) ($item['unread_count'] ?? 0);
                                    $active = ($item['key'] ?? '') === $selectedKey;
                                    $relative = ! empty($item['at'])
                                        ? chat_relative_time(\Illuminate\Support\Carbon::parse($item['at']))
                                        : '';
                                @endphp
                                <li data-chat-v2-row
                                    data-type="{{ $item['type'] }}"
                                    data-unread="{{ $unread > 0 ? '1' : '0' }}"
                                    data-search="{{ $item['search'] ?? '' }}">
                                    <a href="{{ $item['url'] }}"
                                       @class([
                                           'flex min-h-[4.25rem] items-center gap-3 border-b border-slate-50 px-4 py-3.5 no-underline transition active:bg-slate-100 sm:px-3.5 sm:py-3',
                                           'bg-[#F1EBFF]' => $active,
                                           'hover:bg-slate-50' => ! $active,
                                           'bg-indigo-50/40' => ! $active && $unread > 0,
                                       ])>
                                        <span class="relative h-12 w-12 shrink-0 overflow-hidden {{ ($item['type'] ?? '') === 'direct' ? 'rounded-full' : 'rounded-xl' }} bg-slate-100 sm:h-11 sm:w-11">
                                            @if (! empty($item['image_url']))
                                                <img src="{{ $item['image_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center bg-[#7138F5] text-sm font-extrabold text-white">{{ $item['initials'] ?? mb_strtoupper(mb_substr($item['title'] ?? '?', 0, 1)) }}</span>
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center justify-between gap-2">
                                                <span @class(['truncate text-[15px] text-slate-900 sm:text-[13px]', 'font-extrabold' => $unread > 0, 'font-semibold' => $unread === 0]) dir="auto">{{ $item['title'] }}</span>
                                                @if ($relative !== '')
                                                    <time @class(['shrink-0 text-[11px] font-bold sm:text-[10px]', 'text-[#7138F5]' => $unread > 0, 'text-slate-400' => $unread === 0])>{{ $relative }}</time>
                                                @endif
                                            </span>
                                            <span class="mt-1 flex items-center gap-2">
                                                <span @class(['min-w-0 flex-1 truncate text-[13px] sm:text-[12px]', 'font-semibold text-slate-700' => $unread > 0, 'text-slate-500' => $unread === 0]) dir="auto">{{ $item['preview'] }}</span>
                                                @if ($unread > 0)
                                                    <span class="inline-flex min-w-[1.35rem] shrink-0 items-center justify-center rounded-full bg-[#7138F5] px-1.5 py-0.5 text-[11px] font-bold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                                                @endif
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <p data-chat-v2-empty-filter class="hidden px-4 py-10 text-center text-sm text-slate-500">{{ __('chat.inbox_no_results') }}</p>
                    @endif
                </div>
            </aside>

            {{-- Right: active conversation --}}
            <section @class([
                'flex min-w-0 flex-1 flex-col bg-[#F8F9FE]',
                'flex' => $selectedKey !== '',
                'hidden lg:flex' => $selectedKey === '',
            ]) data-chat-v2-panel>
                @if ($panel === 'direct' && $selected)
                    <x-chat.panel-direct
                        :conversation="$selected['conversation']"
                        :messages="$selected['messages']"
                        :other="$selected['other']"
                        :back-url="route('chats.index')"
                    />
                @elseif ($panel === 'event' && $selected)
                    <x-chat.panel-event
                        :event="$selected['event']"
                        :messages="$selected['messages']"
                        :back-url="route('chats.index')"
                    />
                @else
                    <div class="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F1EBFF] text-[#7138F5]">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a8.5 8.5 0 0 1-12.4 7.6L3 21l1.5-4.2A8.5 8.5 0 1 1 21 12Z"/>
                            </svg>
                        </span>
                        <p class="text-sm font-semibold text-slate-700">{{ __('chat.select_conversation') }}</p>
                        <p class="max-w-xs text-xs text-slate-500">{{ __('chat.select_conversation_hint') }}</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/chat-v2.js'])
    @if ($panel === 'direct')
        @vite(['resources/js/dm-chat.js'])
    @elseif ($panel === 'event')
        @vite(['resources/js/event-chat.js'])
    @endif
@endpush
