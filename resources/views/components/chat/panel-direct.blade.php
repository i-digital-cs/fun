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
    data-react-url-template="/api/dms/{{ $conversation->id }}/messages/__MID__/react"
    data-emojis="{{ implode(',', config('chat.emojis')) }}"
>
    <div id="dm-messages" class="event-chat__messages fancy-scroll min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-5" role="log" aria-live="polite">
        @forelse ($messages as $message)
            @php
                $mine = (int) $message->user_id === (int) auth()->id();
                $user = $message->user;
                $photo = $user?->profile_image_path;
                $initials = user_initials($user?->name);
                $timeLabel = $message->created_at?->timezone(config('app.timezone'))->format('g:i A');
                $reply = $message->relationLoaded('replyTo') ? $message->replyTo : null;
            @endphp
            <div class="chat-row {{ $mine ? 'chat-row--mine' : 'chat-row--theirs' }}" data-message-id="{{ $message->id }}" data-body="{{ e($message->body) }}" data-sender="{{ e($user?->name ?? '') }}">
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
                    @if ($reply)
                        <button type="button" class="chat-reply-quote mb-1 w-full rounded-lg bg-black/10 px-2 py-1 text-start text-[11px]" data-jump-to="{{ $reply->id }}">
                            <span class="font-bold">{{ $reply->user?->name }}</span>
                            <span class="block truncate opacity-80">{{ $reply->body }}</span>
                        </button>
                    @endif
                    @if (($message->message_type ?? 'text') === 'voice' && $message->media_path)
                        <audio controls preload="none" class="max-w-full" src="{{ route('dms.media', $message) }}"></audio>
                    @elseif (($message->message_type ?? 'text') === 'image' && $message->media_path)
                        <a href="{{ route('dms.media', $message) }}" target="_blank" rel="noopener">
                            <img src="{{ route('dms.media', $message) }}" alt="" class="max-h-56 w-full max-w-xs rounded-lg object-cover" loading="lazy">
                        </a>
                    @else
                        <p class="chat-row__body whitespace-pre-wrap break-words" dir="auto">{{ $message->body }}</p>
                    @endif
                    <div class="mt-1 flex items-center gap-2">
                        <p class="chat-row__meta text-[10px] opacity-70">{{ $timeLabel }}</p>
                        <button type="button" class="text-[10px] font-bold opacity-70 hover:opacity-100" data-reply-to="{{ $message->id }}">{{ __('chat.reply') }}</button>
                        <button type="button" class="text-[10px] font-bold opacity-70 hover:opacity-100" data-react-open="{{ $message->id }}">{{ __('chat.react') }}</button>
                    </div>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-slate-500" id="dm-empty">{{ __('chat.empty_thread') }}</p>
        @endforelse
    </div>

    <div id="dm-reply-bar" hidden class="flex items-center gap-2 border-t border-slate-100 bg-indigo-50/80 px-3 py-2 text-xs">
        <div class="min-w-0 flex-1">
            <p class="font-bold text-indigo-800" data-reply-name></p>
            <p class="truncate text-slate-600" data-reply-body></p>
        </div>
        <button type="button" id="dm-reply-cancel" class="rounded-lg px-2 py-1 font-bold text-slate-600 hover:bg-white">✕</button>
    </div>

    <form id="dm-form" method="POST" action="{{ route('dms.store', $conversation) }}" enctype="multipart/form-data" class="chat-v2-composer shrink-0 border-t border-slate-200/80 bg-white px-3 pt-3"
          data-i18n-send-failed="{{ __('chat.send_failed') }}"
          data-i18n-voice-ready="{{ __('chat.voice_ready') }}"
          data-i18n-voice-recording="{{ __('chat.voice_recording') }}"
          data-i18n-mic-denied="{{ __('chat.mic_denied') }}"
          data-i18n-mic-unsupported="{{ __('chat.mic_unsupported') }}"
          data-i18n-heic="{{ __('chat.attachment_heic') }}">
        @csrf
        <input type="hidden" name="reply_to_id" id="dm-reply-to" value="">
        <input type="hidden" name="client_uuid" id="dm-client-uuid" value="">
        <input type="hidden" name="media_kind" id="dm-media-kind" value="">
        <input type="hidden" name="duration_ms" id="dm-duration-ms" value="">
        <div data-chat-attach-preview hidden class="rounded-xl border border-slate-200 bg-[#F8F9FE] px-2.5 py-2 text-xs font-semibold text-slate-700">
            <span data-chat-attach-label></span>
            <button type="button" data-chat-attach-clear class="ms-auto text-rose-600 underline">✕</button>
        </div>
        <div class="flex items-end gap-2">
            <button type="button" id="dm-voice-btn" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 hover:border-[#7138F5] hover:text-[#7138F5] sm:h-11 sm:w-11" title="{{ __('chat.voice_note') }}" aria-label="{{ __('chat.voice_note') }}">🎤</button>
            <label class="inline-flex h-12 w-12 shrink-0 cursor-pointer items-center justify-center rounded-2xl border border-slate-200 text-slate-600 hover:border-[#7138F5] hover:text-[#7138F5] sm:h-11 sm:w-11" title="{{ __('chat.attach') }}">
                📎
                <input type="file" id="dm-media" name="media" accept="image/jpeg,image/png,image/webp,image/gif,image/*,audio/*,.pdf,.txt" class="sr-only">
            </label>
            <label class="sr-only" for="dm-body">{{ __('chat.placeholder') }}</label>
            <input
                id="dm-body"
                name="body"
                type="text"
                maxlength="1000"
                autocomplete="off"
                enterkeyhint="send"
                placeholder="{{ __('chat.placeholder') }}"
                class="min-h-12 w-full rounded-2xl border border-slate-200 bg-[#F8F9FE] px-4 py-2.5 text-base outline-none ring-[#7138F5]/20 focus:border-[#7138F5] focus:ring-4 sm:min-h-11 sm:text-sm"
            >
            <button type="submit" id="dm-send" class="inline-flex h-12 shrink-0 items-center justify-center rounded-2xl bg-[#7138F5] px-4 text-sm font-bold text-white hover:bg-[#5825D6] sm:h-11">{{ __('chat.send') }}</button>
        </div>
        <p id="dm-voice-status" class="mt-1 hidden text-[11px] font-semibold text-rose-600" role="status"></p>
    </form>
</div>
