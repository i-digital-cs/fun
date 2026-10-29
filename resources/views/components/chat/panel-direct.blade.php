@props([
    'conversation',
    'messages',
    'other',
    'backUrl' => null,
])

<header class="flex shrink-0 items-center gap-3 border-b border-slate-200/80 bg-white px-3 py-2.5 sm:px-4">
    @if ($backUrl)
        <a href="{{ $backUrl }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="{{ __('chat.inbox_title') }}">
            <svg class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
    @endif
    <a href="{{ $other->publicProfileUrl() }}" class="flex min-w-0 flex-1 items-center gap-2.5 no-underline">
        <span class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-slate-100">
            @if ($other->profile_image_path)
                <img src="{{ $other->profile_image_path }}" alt="" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center bg-[#7138F5] text-xs font-extrabold text-white">{{ user_initials($other->name) }}</span>
            @endif
        </span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-bold text-slate-900">{{ $other->name }}</span>
            <span class="block text-[11px] font-medium text-slate-500">{{ __('friends.dm_title') }}</span>
        </span>
    </a>
</header>

<div
    id="dm-chat"
    class="event-chat chat-v2-thread flex min-h-0 flex-1 flex-col bg-[#F8F9FE]"
    data-conversation-id="{{ $conversation->id }}"
    data-channel="dms.{{ $conversation->id }}"
    data-user-id="{{ auth()->id() }}"
    data-csrf="{{ csrf_token() }}"
    data-post-url="{{ route('dms.store', $conversation) }}"
>
    <div id="dm-messages" class="event-chat__messages fancy-scroll min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-5" role="log" aria-live="polite">
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

    <form id="dm-form" method="POST" action="{{ route('dms.store', $conversation) }}" class="chat-v2-composer shrink-0 border-t border-slate-200/80 bg-white p-3">
        @csrf
        <div class="flex items-end gap-2">
            <label class="sr-only" for="dm-body">{{ __('chat.placeholder') }}</label>
            <input
                id="dm-body"
                name="body"
                type="text"
                maxlength="1000"
                required
                autocomplete="off"
                placeholder="{{ __('chat.placeholder') }}"
                class="min-h-11 w-full rounded-2xl border border-slate-200 bg-[#F8F9FE] px-4 py-2.5 text-sm outline-none ring-[#7138F5]/20 focus:border-[#7138F5] focus:ring-4"
            >
            <button type="submit" class="inline-flex h-11 shrink-0 items-center justify-center rounded-2xl bg-[#7138F5] px-4 text-sm font-bold text-white hover:bg-[#5825D6]">{{ __('chat.send') }}</button>
        </div>
    </form>
</div>
