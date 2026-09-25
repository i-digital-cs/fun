<header class="relative sticky top-0 z-40 flex h-[70px] items-center justify-between overflow-visible border-b border-[#e7ebf3] px-4 sm:px-[1.875rem]">
    {{-- Blur on a separate layer so the live-search dropdown is not clipped --}}
    <div class="pointer-events-none absolute inset-0 z-0 bg-white/90 backdrop-blur-xl" aria-hidden="true"></div>
@php
    $user = auth()->user();
    $locale = app()->getLocale();
    $oppositeLocale = $locale === 'en' ? 'ar' : 'en';
    $initials = user_initials($user?->name);
    $firstName = filled($user?->name) ? explode(' ', trim($user->name))[0] : '';
    $profilePhoto = filled($user?->profile_image_path) ? $user->profile_image_path : null;
@endphp

    <div
        class="live-search relative z-10 w-full max-w-[540px] sm:w-[min(540px,55vw)]"
        data-live-search
        data-live-search-type="events"
        data-live-search-url="{{ route('search.suggest') }}"
        data-live-search-empty="{{ __('ui.search_empty') }}"
        data-live-search-see-all="{{ __('ui.search_see_all') }}"
        data-live-search-error="{{ __('ui.search_failed') }}"
    >
        <form method="GET" action="{{ route('events.index') }}" class="flex h-[42px] w-full items-center gap-2 rounded-xl border border-[#e7ebf3] bg-[#f7f9fc] px-3" role="search">
            <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path stroke-linecap="round" d="M20 20l-3-3"/>
            </svg>
            <input type="search"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="{{ __('dashboard.search_placeholder') }}"
                   aria-label="{{ __('dashboard.search_placeholder') }}"
                   autocomplete="off"
                   data-live-search-input
                   class="min-w-0 flex-1 border-0 bg-transparent text-[13px] text-slate-700 outline-none placeholder:text-slate-400">
        </form>
        <div class="live-search__panel" data-live-search-panel hidden>
            <div class="live-search__searching" data-live-search-searching hidden>
                <x-ui.searching size="sm" />
            </div>
            <div class="live-search__results" data-live-search-results></div>
        </div>
    </div>

    <div class="relative z-10 ms-3 flex shrink-0 items-center gap-3 sm:gap-4">
        <form method="POST" action="{{ route('locale.update') }}" class="hidden sm:inline-flex">
            @csrf
            <input type="hidden" name="locale" value="{{ $oppositeLocale }}">
            <button type="submit"
                    class="flex h-8 items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 text-[11px] font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700"
                    aria-label="{{ __('ui.language') }}">
                <span class="font-black text-indigo-500">{{ $locale === 'en' ? 'ع' : 'EN' }}</span>
                {{ $locale === 'en' ? 'AR' : 'EN' }}
            </button>
        </form>

        <a href="{{ route('notifications.index') }}"
           class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:text-slate-900"
           aria-label="{{ __('notifications.title') }}">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            @php
                $notifyUnread = $user
                    ? app(\App\Services\Notifications\NotificationInboxService::class)->unreadCount($user)
                    : 0;
            @endphp
            <span data-notify-unread-dot
                  @class([
                      'absolute end-1 top-1 h-[7px] w-[7px] rounded-full border-2 border-white bg-orange-500',
                      'hidden' => $notifyUnread < 1,
                  ])
                  @if ($notifyUnread < 1) hidden @endif></span>
        </a>

        <a href="{{ route('chats.index') }}"
           class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:text-slate-900"
           aria-label="{{ __('chat.inbox_title') }}">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
            </svg>
            @php
                $chatUnread = $user
                    ? app(\App\Services\Events\Chat\ListUserChatsService::class)->unreadTotal($user)
                    : 0;
            @endphp
            <span data-chat-unread-dot
                  @class([
                      'absolute end-1 top-1 h-[7px] w-[7px] rounded-full border-2 border-white bg-indigo-600',
                      'hidden' => $chatUnread < 1,
                  ])
                  @if ($chatUnread < 1) hidden @endif></span>
        </a>

        <details class="relative hidden md:block">
            <summary class="flex cursor-pointer list-none items-center gap-2 marker:content-none [&::-webkit-details-marker]:hidden">
                @if ($profilePhoto)
                    <img src="{{ $profilePhoto }}" alt="" class="h-9 w-9 rounded-full object-cover bg-slate-200">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $initials }}</span>
                @endif
                <span class="text-xs font-bold text-slate-800">{{ $firstName }}</span>
                <svg class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 9l6 6 6-6"/>
                </svg>
            </summary>
            <div class="absolute end-0 top-full z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                <a href="{{ route('home') }}" class="block whitespace-nowrap px-4 py-2 text-sm font-medium text-slate-700 no-underline hover:bg-slate-50">
                    {{ __('ui.home') }}
                </a>
                <a href="{{ route('settings.profile.edit') }}" class="block whitespace-nowrap px-4 py-2 text-sm font-medium text-slate-700 no-underline hover:bg-slate-50">
                    {{ __('dashboard.nav_profile') }}
                </a>
                <a href="{{ route('settings.location.edit') }}"
                   data-location-modal-open
                   class="block whitespace-nowrap px-4 py-2 text-sm font-medium text-slate-700 no-underline hover:bg-slate-50">
                    {{ __('dashboard.nav_location') }}
                </a>
                <a href="{{ route('identity.create') }}" class="block whitespace-nowrap px-4 py-2 text-sm font-medium text-slate-700 no-underline hover:bg-slate-50">
                    {{ __('dashboard.nav_identity') }}
                    @if ($user?->needsIdentityDocuments())
                        <span class="ms-1 inline-block h-1.5 w-1.5 rounded-full bg-orange-500 align-middle"></span>
                    @endif
                </a>
                <a href="{{ route('blocks.index') }}" class="block whitespace-nowrap px-4 py-2 text-sm font-medium text-slate-700 no-underline hover:bg-slate-50">
                    {{ __('dashboard.nav_blocked') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full whitespace-nowrap px-4 py-2 text-start text-sm font-medium text-slate-600 hover:bg-slate-50">
                        {{ __('ui.logout') }}
                    </button>
                </form>
            </div>
        </details>

        <a href="{{ route('settings.profile.edit') }}"
                class="flex items-center gap-2 md:hidden"
                aria-label="{{ __('dashboard.nav_profile') }}">
            @if ($profilePhoto)
                <img src="{{ $profilePhoto }}" alt="" class="h-9 w-9 rounded-full object-cover bg-slate-200">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $initials }}</span>
            @endif
        </a>
    </div>
</header>
