@php
    $brand = site_display_name();
    $locale = app()->getLocale();
    $oppositeLocale = $locale === 'en' ? 'ar' : 'en';
    $user = auth()->user();
    $canParticipate = $user?->canParticipate() === true;
    $cityLabel = $user?->city?->localizedName() ?? __('design.location_default');
    $initials = user_initials($user?->name);
    $isHome = request()->routeIs('home');
    $isNearby = request()->routeIs('events.index');
    $isMine = request()->routeIs('events.mine');
@endphp

<header class="relative sticky top-0 z-50 overflow-visible border-b border-white/60 shadow-[0_8px_30px_rgba(15,23,42,0.05)]">
    <div class="pointer-events-none absolute inset-0 z-0 bg-white/70 backdrop-blur-xl" aria-hidden="true"></div>
    <nav class="relative z-10 mx-auto flex min-h-16 max-w-7xl items-center gap-3 px-4 py-3 sm:px-6" aria-label="{{ __('ui.primary_nav') }}">
        <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5 no-underline" aria-label="{{ $brand }}">
            @if ($siteLogoUrl ?? site_logo_url())
                <img src="{{ $siteLogoUrl ?? site_logo_url() }}" alt="{{ $brand }}" class="h-10 w-auto max-w-[2.75rem] rounded-2xl object-contain shadow-lg shadow-indigo-600/20 transition-transform duration-300 group-hover:scale-105">
            @else
                <span class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white shadow-lg shadow-indigo-600/20 transition-transform duration-300 group-hover:scale-105">
                    <x-brand.mark :size="28" />
                    <span class="absolute -end-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-orange-400 ring-2 ring-white"></span>
                </span>
            @endif
            <span class="hidden flex-col leading-none sm:flex">
                <span class="text-[15px] font-extrabold tracking-tight text-slate-900">{{ $brand }}</span>
                <span class="mt-0.5 flex items-center gap-1 text-[10px] font-semibold uppercase tracking-widest text-indigo-500">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                    </svg>
                    {{ $cityLabel }}
                </span>
            </span>
        </a>

        <div class="mx-2 hidden items-center gap-1 lg:flex">
            <a href="{{ route('home') }}"
               @class([
                   'rounded-full bg-indigo-600/10 px-4 py-2 text-sm font-semibold text-indigo-700' => $isHome,
                   'rounded-full px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900' => ! $isHome,
               ])
               @if ($isHome) aria-current="page" @endif>
                {{ __('ui.home') }}
            </a>
            @auth
                <a href="{{ route('dashboard') }}"
                   @class([
                       'rounded-full bg-indigo-600/10 px-4 py-2 text-sm font-semibold text-indigo-700' => request()->routeIs('dashboard'),
                       'rounded-full px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('dashboard'),
                   ])
                   @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                    {{ __('ui.dashboard') }}
                </a>
            @endauth
            <a href="{{ route('events.index') }}"
               @class([
                   'rounded-full px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900',
               ])>
                {{ __('design.nav_nearby_short') }}
            </a>
            @auth
                <a href="{{ route('events.mine') }}"
                   @class([
                       'rounded-full bg-indigo-600/10 px-4 py-2 text-sm font-semibold text-indigo-700' => $isMine,
                       'rounded-full px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900' => ! $isMine,
                   ])>
                    {{ __('events.my_events') }}
                </a>
                <a href="{{ route('events.mine') }}" class="relative rounded-full px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    <span class="flex items-center gap-1.5">
                        {{ __('ui.chat') }}
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-orange-500"></span>
                        </span>
                    </span>
                </a>
            @endauth
        </div>

        <div
            class="live-search relative mx-2 hidden min-w-0 flex-1 xl:block"
            data-live-search
            data-live-search-type="events"
            data-live-search-url="{{ route('search.suggest') }}"
            data-live-search-empty="{{ __('ui.search_empty') }}"
            data-live-search-see-all="{{ __('ui.search_see_all') }}"
            data-live-search-error="{{ __('ui.search_failed') }}"
        >
            <form method="GET" action="{{ route('events.index') }}" class="flex w-full items-center gap-2 rounded-full border border-slate-200/80 bg-slate-100/80 px-3 py-2 transition focus-within:border-indigo-300 focus-within:bg-white focus-within:ring-4 focus-within:ring-indigo-100" role="search">
                <span class="hidden shrink-0 items-center gap-1 rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-bold text-indigo-700 sm:flex">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                    </svg>
                    {{ $cityLabel }}
                </span>
                <span class="h-4 w-px shrink-0 bg-slate-300" aria-hidden="true"></span>
                <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" d="M20 20l-3-3"/>
                </svg>
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('design.search_ph') }}"
                       aria-label="{{ __('design.search_ph') }}"
                       autocomplete="off"
                       data-live-search-input
                       class="min-w-0 flex-1 bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400">
            </form>
            <div class="live-search__panel" data-live-search-panel hidden>
                <div class="live-search__searching" data-live-search-searching hidden>
                    <x-ui.searching size="sm" />
                </div>
                <div class="live-search__results" data-live-search-results></div>
            </div>
        </div>

        <div class="ms-auto flex items-center gap-2 lg:ms-0">
            <form method="POST" action="{{ route('locale.update') }}" class="inline-flex">
                @csrf
                <input type="hidden" name="locale" value="{{ $oppositeLocale }}">
                <button type="submit"
                        class="flex h-9 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700"
                        aria-label="{{ __('ui.language') }}">
                    <span class="text-[11px] font-black text-indigo-500">{{ $locale === 'en' ? 'ع' : 'EN' }}</span>
                    {{ $locale === 'en' ? 'AR' : 'EN' }}
                </button>
            </form>

            @if ($canParticipate)
                <a href="{{ route('events.create') }}" class="hidden h-9 items-center gap-1.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-4 text-sm font-bold text-white shadow-lg shadow-orange-500/30 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-orange-500/40 active:translate-y-0 sm:inline-flex">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    {{ __('events.create_event') }}
                </a>
            @elseif ($user === null)
                <a href="{{ route('register') }}" class="hidden h-9 items-center gap-1.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-4 text-sm font-bold text-white shadow-lg shadow-orange-500/30 transition hover:-translate-y-0.5 sm:inline-flex">
                    {{ __('ui.register') }}
                </a>
                <a href="{{ route('login') }}" class="hidden text-sm font-medium text-slate-600 hover:text-indigo-700 sm:inline">{{ __('ui.login') }}</a>
            @elseif ($user->needsIdentityDocuments())
                <a href="{{ route('identity.create') }}" class="hidden h-9 items-center gap-1.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-4 text-sm font-bold text-white shadow-lg shadow-orange-500/30 sm:inline-flex">
                    {{ __('identity.title') }}
                </a>
            @endif

            @if ($canParticipate)
                <svg class="hidden h-5 w-5 text-emerald-500 md:block" viewBox="0 0 24 24" fill="currentColor" aria-label="{{ __('home.active_account') }}">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            @endif

            @auth
                <a href="{{ route('notifications.index') }}" class="relative hidden h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:text-slate-900 md:flex" aria-label="{{ __('notifications.title') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @php
                        $notifyUnread = $user
                            ? app(\App\Services\Notifications\NotificationInboxService::class)->unreadCount($user)
                            : 0;
                    @endphp
                    <span data-notify-unread-dot
                          @class([
                              'absolute end-2 top-2 h-2 w-2 rounded-full bg-orange-500 ring-2 ring-white',
                              'hidden' => $notifyUnread < 1,
                          ])
                          @if ($notifyUnread < 1) hidden @endif></span>
                </a>

                <a href="{{ route('chats.index') }}" class="relative hidden h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:text-slate-900 md:flex" aria-label="{{ __('chat.inbox_title') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                    </svg>
                    @php
                        $chatUnread = $user
                            ? app(\App\Services\Events\Chat\ListUserChatsService::class)->unreadTotal($user)
                            : 0;
                    @endphp
                    <span data-chat-unread-dot
                          @class([
                              'absolute end-2 top-2 h-2 w-2 rounded-full bg-indigo-600 ring-2 ring-white',
                              'hidden' => $chatUnread < 1,
                          ])
                          @if ($chatUnread < 1) hidden @endif></span>
                </a>

                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1.5 marker:content-none [&::-webkit-details-marker]:hidden" aria-label="{{ $user->name }}">
                        <span class="rounded-full bg-gradient-to-tr from-indigo-600 via-violet-500 to-orange-400 p-[2px] transition hover:scale-105">
                            <span class="relative flex h-8 w-8 items-center justify-center rounded-full bg-white text-[13px] font-black text-indigo-700">
                                {{ $initials }}
                                @if ($canParticipate)
                                    <span class="absolute -bottom-0.5 -end-0.5 h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                                @endif
                            </span>
                        </span>
                        <svg class="hidden h-3 w-3 text-slate-400 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M6 9l6 6 6-6"/>
                        </svg>
                    </summary>
                    <div class="absolute end-0 top-full z-40 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200/80 bg-white py-1.5 shadow-xl shadow-slate-900/10">
                        <p class="truncate px-4 py-2 text-xs font-semibold text-slate-400">{{ $user->name }}</p>
                        <a href="{{ route('dashboard') }}" class="block whitespace-nowrap px-4 py-2.5 text-sm font-medium text-slate-700 no-underline hover:bg-indigo-50 hover:text-indigo-700">
                            {{ __('ui.dashboard') }}
                        </a>
                        <a href="{{ route('settings.location.edit') }}"
                           data-location-modal-open
                           class="block whitespace-nowrap px-4 py-2.5 text-sm font-medium text-slate-700 no-underline hover:bg-indigo-50 hover:text-indigo-700">
                            {{ __('dashboard.nav_location') }}
                        </a>
                        <a href="{{ route('blocks.index') }}" class="block whitespace-nowrap px-4 py-2.5 text-sm font-medium text-slate-700 no-underline hover:bg-indigo-50 hover:text-indigo-700">
                            {{ __('dashboard.nav_blocked') }}
                        </a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full whitespace-nowrap px-4 py-2.5 text-start text-sm font-medium text-red-600 hover:bg-red-50">
                                {{ __('ui.logout') }}
                            </button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="rounded-full bg-gradient-to-tr from-indigo-600 via-violet-500 to-orange-400 p-[2px] sm:hidden">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[13px] font-black text-indigo-700">?</span>
                </a>
            @endauth

            <details class="lg:hidden">
                <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 marker:content-none [&::-webkit-details-marker]:hidden" aria-label="{{ __('ui.menu') }}">
                    <svg class="h-4 w-4 open:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                    <svg class="hidden h-4 w-4 open:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </summary>
                <div class="absolute inset-x-0 top-full overflow-hidden border-t border-slate-100 bg-white/95 backdrop-blur-xl">
                    <div class="mx-auto max-w-7xl space-y-1 px-4 py-3 sm:px-6">
                        <a href="{{ route('home') }}" @class(['flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold', 'bg-indigo-50 text-indigo-700' => $isHome, 'text-slate-600 hover:bg-slate-50' => ! $isHome])>
                            @if ($isHome)<span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>@endif
                            {{ __('ui.home') }}
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}" @class(['flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold', 'bg-indigo-50 text-indigo-700' => request()->routeIs('dashboard'), 'text-slate-600 hover:bg-slate-50' => ! request()->routeIs('dashboard')])>
                                @if (request()->routeIs('dashboard'))<span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>@endif
                                {{ __('ui.dashboard') }}
                            </a>
                        @endauth
                        <a href="{{ route('events.index') }}" @class(['flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold', 'bg-indigo-50 text-indigo-700' => $isNearby, 'text-slate-600 hover:bg-slate-50' => ! $isNearby])>
                            @if ($isNearby)<span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>@endif
                            {{ __('design.nav_nearby_short') }}
                        </a>
                        @auth
                            <a href="{{ route('events.mine') }}" @class(['flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold', 'bg-indigo-50 text-indigo-700' => $isMine, 'text-slate-600 hover:bg-slate-50' => ! $isMine])>
                                @if ($isMine)<span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>@endif
                                {{ __('events.my_events') }}
                            </a>
                            <a href="{{ route('events.mine') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                {{ __('ui.chat') }}
                            </a>
                        @endauth
                        <div
                            class="live-search relative"
                            data-live-search
                            data-live-search-type="events"
                            data-live-search-url="{{ route('search.suggest') }}"
                            data-live-search-empty="{{ __('ui.search_empty') }}"
                            data-live-search-see-all="{{ __('ui.search_see_all') }}"
                            data-live-search-error="{{ __('ui.search_failed') }}"
                        >
                            <form method="GET" action="{{ route('events.index') }}" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"/>
                                    <path stroke-linecap="round" d="M20 20l-3-3"/>
                                </svg>
                                <input type="search" name="q" value="{{ request('q') }}"
                                       placeholder="{{ __('design.search_ph') }}"
                                       aria-label="{{ __('design.search_ph') }}"
                                       autocomplete="off"
                                       data-live-search-input
                                       class="w-full bg-transparent text-sm outline-none placeholder:text-slate-400">
                            </form>
                            <div class="live-search__panel" data-live-search-panel hidden>
                                <div class="live-search__searching" data-live-search-searching hidden>
                                    <x-ui.searching size="sm" />
                                </div>
                                <div class="live-search__results" data-live-search-results></div>
                            </div>
                        </div>
                        @if ($canParticipate)
                            <a href="{{ route('events.create') }}" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/30">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                                </svg>
                                {{ __('events.create_event') }}
                            </a>
                        @elseif ($user === null)
                            <a href="{{ route('register') }}" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/30">
                                {{ __('ui.register') }}
                            </a>
                        @endif
                        <p class="flex items-center gap-1.5 px-1 pt-1 text-[11px] font-medium text-slate-400">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                            </svg>
                            {{ __('design.discover_sub') }}
                        </p>
                    </div>
                </div>
            </details>
        </div>
    </nav>
</header>
