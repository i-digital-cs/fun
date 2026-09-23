@extends('layouts.dashboard')

@section('title', __('chat.inbox_title'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('chat.inbox_title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('chat.inbox_subtitle') }}</p>
            </div>
            @if ($unreadTotal > 0)
                <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">
                    {{ trans_choice('chat.unread_count', $unreadTotal, ['count' => $unreadTotal]) }}
                </span>
            @endif
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
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <ul class="divide-y divide-slate-100" role="list">
                    @foreach ($chats as $chat)
                        @php
                            $last = $chat['last_message'] ?? null;
                            $unread = (int) ($chat['unread_count'] ?? 0);
                            $preview = $last['body'] ?? __('chat.inbox_no_messages');
                            $who = $last
                                ? (($last['is_mine'] ?? false) ? __('chat.you').': ' : (($last['user']['name'] ?? '').': '))
                                : '';
                        @endphp
                        <li>
                            <a href="{{ $chat['chat_url'] }}"
                               class="flex items-start gap-3 px-4 py-4 no-underline transition hover:bg-indigo-50/60 sm:px-5">
                                <span @class([
                                    'mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-sm font-extrabold',
                                    'bg-indigo-600 text-white' => $unread > 0,
                                    'bg-slate-100 text-slate-600' => $unread === 0,
                                ])>
                                    {{ mb_strtoupper(mb_substr($chat['title'], 0, 1)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-2">
                                        <span @class([
                                            'truncate text-sm text-slate-900',
                                            'font-extrabold' => $unread > 0,
                                            'font-bold' => $unread === 0,
                                        ])>{{ $chat['title'] }}</span>
                                        @if ($last['created_at'] ?? null)
                                            <time class="shrink-0 text-[11px] font-medium text-slate-400">
                                                {{ \Illuminate\Support\Carbon::parse($last['created_at'])->timezone(config('app.timezone'))->diffForHumans(short: true) }}
                                            </time>
                                        @endif
                                    </span>
                                    @if ($chat['city'] ?? null)
                                        <span class="mt-0.5 block text-[11px] font-medium text-slate-400">{{ $chat['city'] }}</span>
                                    @endif
                                    <span @class([
                                        'mt-1 block truncate text-sm',
                                        'font-semibold text-slate-800' => $unread > 0,
                                        'text-slate-500' => $unread === 0,
                                    ])>{{ $who }}{{ $preview }}</span>
                                </span>
                                @if ($unread > 0)
                                    <span class="mt-1 inline-flex min-w-[1.4rem] shrink-0 items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-[11px] font-bold text-white">
                                        {{ $unread > 99 ? '99+' : $unread }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
