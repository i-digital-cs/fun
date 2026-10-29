@props([
    'event',
    'messages',
    'backUrl' => null,
])

<header class="flex shrink-0 items-center gap-3 border-b border-slate-200/80 bg-white px-3 py-2.5 sm:px-4">
    @if ($backUrl)
        <a href="{{ $backUrl }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="{{ __('chat.inbox_title') }}">
            <svg class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
    @endif
    <a href="{{ event_show_url($event) }}" class="flex min-w-0 flex-1 items-center gap-2.5 no-underline">
        <span class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-slate-100">
            <x-events.cover-img :event="$event" size="card" class="h-full w-full object-cover" />
        </span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-bold text-slate-900 bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</span>
            <span class="block truncate text-[11px] font-medium text-slate-500">
                {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A') }}
                @if ($event->city) · {{ $event->city->localizedName() }} @endif
            </span>
        </span>
    </a>
    <a href="{{ event_show_url($event) }}" class="hidden shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-700 hover:border-[#7138F5] hover:text-[#7138F5] sm:inline-flex">{{ __('events.view_event') }}</a>
</header>

<div
    id="event-chat"
    class="event-chat chat-v2-thread flex min-h-0 flex-1 flex-col bg-[#F8F9FE]"
    data-event-id="{{ $event->id }}"
    data-owner-id="{{ $event->created_by }}"
    data-host-label="{{ __('home.host') }}"
    data-post-url="{{ event_route('events.chat.store', $event) }}"
    data-channel="events.{{ $event->id }}.chat"
    data-user-id="{{ auth()->id() }}"
    data-csrf="{{ csrf_token() }}"
    data-report-url="{{ route('reports.create') }}"
    data-report-label="{{ __('safety.report') }}"
    data-react-url-template="{{ event_route('events.chat.react', $event, ['message' => '__MID__']) }}"
    data-reactions-url-template="{{ event_route('events.chat.reactions', $event, ['message' => '__MID__']) }}"
    data-like-label="{{ __('chat.like') }}"
    data-dislike-label="{{ __('chat.dislike') }}"
    data-reactions-title="{{ __('chat.reactions_title') }}"
    data-liked-by-label="{{ __('chat.liked_by') }}"
    data-disliked-by-label="{{ __('chat.disliked_by') }}"
    data-no-likes-label="{{ __('chat.no_likes_yet') }}"
    data-no-dislikes-label="{{ __('chat.no_dislikes_yet') }}"
    data-see-who-label="{{ __('chat.see_who_reacted') }}"
    data-emojis="{{ implode(',', config('chat.emojis')) }}"
>
    <div id="chat-messages" class="event-chat__messages fancy-scroll min-h-0 flex-1 overflow-x-hidden overflow-y-auto px-3 py-4 sm:px-5" role="log" aria-live="polite">
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
                $reply = $message->relationLoaded('replyTo') ? $message->replyTo : null;
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
                    data-body="{{ e($message->body) }}"
                    data-sender="{{ e($user?->name ?? '') }}"
                    @if ($isHost) data-host="1" @endif
                >
                    @unless ($mine)
                        @if ($profileUrl)
                            <a href="{{ $profileUrl }}" class="chat-row__avatar" title="{{ $user?->name }}">
                                @if (filled($photo)) <img src="{{ $photo }}" alt="" loading="lazy" /> @else <span>{{ $initials }}</span> @endif
                            </a>
                        @else
                            <span class="chat-row__avatar" aria-hidden="true">
                                @if (filled($photo)) <img src="{{ $photo }}" alt="" loading="lazy" /> @else <span>{{ $initials }}</span> @endif
                            </span>
                        @endif
                    @endunless

                    <div class="chat-message {{ $mine ? 'chat-message--mine' : 'chat-message--theirs' }}{{ $isHost ? ' chat-message--host' : '' }}{{ ($message->my_reaction ?? null) === 'like' ? ' chat-message--reacted-like' : (($message->my_reaction ?? null) === 'dislike' ? ' chat-message--reacted-dislike' : '') }}">
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
                        @endunless
                        @if ($reply)
                            <button type="button" class="chat-reply-quote mb-1 w-full rounded-lg bg-black/10 px-2 py-1 text-start text-[11px]" data-jump-to="{{ $reply->id }}">
                                <span class="font-bold">{{ $reply->user?->name }}</span>
                                <span class="block truncate opacity-80">{{ $reply->body }}</span>
                            </button>
                        @endif
                        @if (($message->message_type ?? 'text') === 'voice' && $message->media_path)
                            <audio controls preload="none" class="mb-1 max-w-full" src="{{ route('events.chat.media', [$event, $message]) }}"></audio>
                        @elseif (($message->message_type ?? 'text') === 'image' && $message->media_path)
                            <a href="{{ route('events.chat.media', [$event, $message]) }}" target="_blank" rel="noopener" class="mb-1 block">
                                <img src="{{ route('events.chat.media', [$event, $message]) }}" alt="" class="max-h-56 w-full max-w-xs rounded-lg object-cover" loading="lazy">
                            </a>
                        @else
                            <p class="chat-message__body whitespace-pre-wrap break-words">{{ $message->body }}</p>
                        @endif
                        <div class="chat-message__footer">
                            <span class="chat-message__time">{{ $timeLabel }}</span>
                            <button type="button" class="text-[10px] font-bold opacity-70 hover:opacity-100" data-reply-to="{{ $message->id }}">{{ __('chat.reply') }}</button>
                            @unless ($mine)
                                <button type="button" class="chat-message__react" data-react="like" aria-label="{{ __('chat.like') }}">👍 <span data-like-count>{{ (int) ($message->likes_count ?? 0) }}</span></button>
                                <button type="button" class="chat-message__react" data-react="dislike" aria-label="{{ __('chat.dislike') }}">👎 <span data-dislike-count>{{ (int) ($message->dislikes_count ?? 0) }}</span></button>
                            @endunless
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <p class="py-10 text-center text-sm text-slate-500" id="chat-empty">{{ __('chat.empty_thread') }}</p>
        @endforelse
    </div>

    <div id="chat-reply-bar" hidden class="flex items-center gap-2 border-t border-slate-100 bg-indigo-50/80 px-3 py-2 text-xs">
        <div class="min-w-0 flex-1">
            <p class="font-bold text-indigo-800" data-reply-name></p>
            <p class="truncate text-slate-600" data-reply-body></p>
        </div>
        <button type="button" id="chat-reply-cancel" class="rounded-lg px-2 py-1 font-bold text-slate-600 hover:bg-white">✕</button>
    </div>

    <form id="chat-form" method="POST" action="{{ event_route('events.chat.store', $event) }}" enctype="multipart/form-data" class="chat-v2-composer shrink-0 border-t border-slate-200/80 bg-white px-3 pt-3"
          data-i18n-send-failed="{{ __('chat.send_failed') }}"
          data-i18n-voice-ready="{{ __('chat.voice_ready') }}"
          data-i18n-voice-recording="{{ __('chat.voice_recording') }}"
          data-i18n-mic-denied="{{ __('chat.mic_denied') }}"
          data-i18n-mic-unsupported="{{ __('chat.mic_unsupported') }}"
          data-i18n-heic="{{ __('chat.attachment_heic') }}">
        @csrf
        <input type="hidden" name="reply_to_id" id="chat-reply-to" value="">
        <input type="hidden" name="client_uuid" id="chat-client-uuid" value="">
        <input type="hidden" name="media_kind" id="chat-media-kind" value="">
        <input type="hidden" name="duration_ms" id="chat-duration-ms" value="">
        <div data-chat-attach-preview hidden class="rounded-xl border border-slate-200 bg-[#F8F9FE] px-2.5 py-2 text-xs font-semibold text-slate-700">
            <span data-chat-attach-label></span>
            <button type="button" data-chat-attach-clear class="ms-auto text-rose-600 underline">✕</button>
        </div>
        <div class="flex items-end gap-2">
            <button type="button" id="chat-voice-btn" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 hover:border-[#7138F5] hover:text-[#7138F5] sm:h-11 sm:w-11" title="{{ __('chat.voice_note') }}" aria-label="{{ __('chat.voice_note') }}">🎤</button>
            <label class="inline-flex h-12 w-12 shrink-0 cursor-pointer items-center justify-center rounded-2xl border border-slate-200 text-slate-600 hover:border-[#7138F5] hover:text-[#7138F5] sm:h-11 sm:w-11" title="{{ __('chat.attach') }}">
                📎
                <input type="file" id="chat-media" name="media" accept="image/jpeg,image/png,image/webp,image/gif,image/*,audio/*,.pdf,.txt" class="sr-only">
            </label>
            <label class="sr-only" for="chat-body">{{ __('chat.placeholder') }}</label>
            <textarea
                id="chat-body"
                name="body"
                rows="1"
                maxlength="1000"
                enterkeyhint="send"
                placeholder="{{ __('chat.placeholder') }}"
                class="max-h-32 min-h-12 w-full resize-none rounded-2xl border border-slate-200 bg-[#F8F9FE] px-4 py-2.5 text-base outline-none ring-[#7138F5]/20 focus:border-[#7138F5] focus:ring-4 sm:min-h-11 sm:text-sm"
            ></textarea>
            <button type="submit" id="chat-send" class="inline-flex h-12 shrink-0 items-center justify-center rounded-2xl bg-[#7138F5] px-4 text-sm font-bold text-white hover:bg-[#5825D6] sm:h-11">{{ __('chat.send') }}</button>
        </div>
        <p id="chat-status" class="event-chat__status mt-1 hidden text-[11px] text-amber-700" role="status"></p>
    </form>
</div>
