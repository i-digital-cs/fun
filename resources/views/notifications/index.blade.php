@extends('layouts.dashboard')

@section('title', __('notifications.title'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('notifications.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('notifications.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($unreadCount > 0)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">
                        {{ trans_choice('notifications.unread_count', $unreadCount, ['count' => $unreadCount]) }}
                    </span>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700">
                            {{ __('notifications.mark_all_read') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('status'))
            <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        @if ($notifications->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                <p class="text-base font-bold text-slate-800">{{ __('notifications.empty_title') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ __('notifications.empty_body') }}</p>
            </div>
        @else
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <ul class="divide-y divide-slate-100" role="list">
                    @foreach ($notifications as $notification)
                        @php
                            $data = is_array($notification->data) ? $notification->data : [];
                            $unread = $notification->read_at === null;
                            $title = $data['title'] ?? __('notifications.title');
                            $body = $data['body'] ?? '';
                            $url = $data['url'] ?? null;
                        @endphp
                        <li @class(['bg-indigo-50/40' => $unread])>
                            @if ($url)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex w-full items-start gap-3 px-4 py-4 text-start transition hover:bg-indigo-50/60 sm:px-5">
                                        <span @class([
                                            'mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl',
                                            'bg-indigo-600 text-white' => $unread,
                                            'bg-slate-100 text-slate-500' => ! $unread,
                                        ])>
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-start justify-between gap-2">
                                                <span @class([
                                                    'text-sm text-slate-900',
                                                    'font-extrabold' => $unread,
                                                    'font-bold' => ! $unread,
                                                ])>{{ $title }}</span>
                                                <time class="shrink-0 text-[11px] font-medium text-slate-400">
                                                    {{ $notification->created_at?->diffForHumans(short: true) }}
                                                </time>
                                            </span>
                                            <span @class([
                                                'mt-1 block text-sm',
                                                'font-semibold text-slate-800' => $unread,
                                                'text-slate-500' => ! $unread,
                                            ])>{{ $body }}</span>
                                        </span>
                                        @if ($unread)
                                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-600"></span>
                                        @endif
                                    </button>
                                </form>
                            @else
                                <div class="flex items-start gap-3 px-4 py-4 sm:px-5">
                                    <span class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-slate-900">{{ $title }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $body }}</p>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
