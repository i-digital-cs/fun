@props([
    'categories' => collect(),
])

@php
    $brand = site_display_name();
    $user = auth()->user();
    $canParticipate = $user?->canParticipate() === true;
    $createRoute = $canParticipate ? route('events.create') : route('identity.create');
    $cityLabel = $user?->city?->localizedName() ?? __('home.location_default');
    $distanceKm = $user?->distance_km ?? config('location.default_distance_km');
    $isHome = request()->routeIs('dashboard');
    $isDiscover = request()->routeIs('events.index');
    $isMine = request()->routeIs('events.mine');
    $isChats = request()->routeIs('chats.*', 'events.chat', 'dms.*');
    $isFriends = request()->routeIs('friends.*');
    $isNotifications = request()->routeIs('notifications.*');
    $isFavorites = request()->routeIs('events.favorites');
    $isCreate = request()->routeIs('events.create');
    $needsIdentity = $user?->needsIdentityDocuments() === true;
@endphp

<aside class="dash-sidebar relative min-h-screen pb-28">
    {{-- V3: brand mark always opens the public marketing home `/` (never `/dashboard`). Auth is preserved. --}}
    <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2.5 px-2.5 pb-1 no-underline" aria-label="{{ $brand }}">
        @if ($siteLogoUrl ?? site_logo_url())
            <img src="{{ $siteLogoUrl ?? site_logo_url() }}" alt="{{ $brand }}" class="h-10 w-auto max-w-[2.75rem] rounded-[13px] object-contain shadow-lg shadow-indigo-600/20">
        @else
            <span class="flex h-10 w-10 items-center justify-center rounded-[13px] bg-white shadow-lg shadow-indigo-600/20">
                <x-brand.mark :size="28" />
            </span>
        @endif
        <span class="text-xl font-extrabold tracking-tight text-slate-900">{{ $brand }}</span>
    </a>

    <nav class="space-y-0.5" aria-label="{{ __('ui.primary_nav') }}">
        <a href="{{ route('dashboard') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-[#F3EEFF] text-[#7138F5]' => $isHome,
               'text-slate-500 hover:bg-[#F3EEFF] hover:text-[#7138F5]' => ! $isHome,
           ])
           @if ($isHome) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/>
            </svg>
            {{ __('dashboard.nav_home') }}
        </a>

        <a href="{{ route('events.index') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-[#F3EEFF] text-[#7138F5]' => $isDiscover,
               'text-slate-500 hover:bg-[#F3EEFF] hover:text-[#7138F5]' => ! $isDiscover,
           ])
           @if ($isDiscover) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M12 3v3M12 18v3M3 12h3M18 12h3"/>
            </svg>
            {{ __('dashboard.nav_discover') }}
        </a>

        <a href="{{ route('events.mine') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-[#F3EEFF] text-[#7138F5]' => $isMine,
               'text-slate-500 hover:bg-[#F3EEFF] hover:text-[#7138F5]' => ! $isMine,
           ])
           @if ($isMine) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
            {{ __('dashboard.nav_my_events') }}
        </a>

        <a href="{{ route('chats.index') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-[#F3EEFF] text-[#7138F5]' => $isChats,
               'text-slate-500 hover:bg-[#F3EEFF] hover:text-[#7138F5]' => ! $isChats,
           ])
           @if ($isChats) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
            </svg>
            {{ __('dashboard.nav_chats') }}
        </a>

        <a href="{{ route('friends.index') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-[#F3EEFF] text-[#7138F5]' => $isFriends,
               'text-slate-500 hover:bg-[#F3EEFF] hover:text-[#7138F5]' => ! $isFriends,
           ])
           @if ($isFriends) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
            {{ __('dashboard.nav_friends') }}
        </a>

        <a href="{{ route('notifications.index') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-[#F3EEFF] text-[#7138F5]' => $isNotifications,
               'text-slate-500 hover:bg-[#F3EEFF] hover:text-[#7138F5]' => ! $isNotifications,
           ])
           @if ($isNotifications) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            {{ __('dashboard.nav_notifications') }}
        </a>

        <a href="{{ route('events.favorites') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-[#F3EEFF] text-[#7138F5]' => $isFavorites,
               'text-slate-500 hover:bg-[#F3EEFF] hover:text-[#7138F5]' => ! $isFavorites,
           ])
           @if ($isFavorites) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                <path d="M27 4v27a1 1 0 0 1-1.625.781L16 24.281l-9.375 7.5A1 1 0 0 1 5 31V4a4 4 0 0 1 4-4h14a4 4 0 0 1 4 4z"/>
            </svg>
            {{ __('dashboard.nav_saved') }}
        </a>

        <a href="{{ $createRoute }}"
           @class([
               'mt-2 flex items-center justify-center gap-2 rounded-[11px] px-3 py-2.5 text-[13px] font-extrabold no-underline transition',
               'bg-[#7138F5] text-white shadow-md shadow-[#7138F5]/25 hover:bg-[#5825D6]' => ! $isCreate,
               'bg-[#5825D6] text-white' => $isCreate,
           ])>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
            </svg>
            {{ __('dashboard.nav_create') }}
        </a>
    </nav>

    <div class="mt-auto px-1 pb-2 pt-6">
        <div class="rounded-[1.1rem] bg-[#F3EEFF] p-3.5">
            <p class="text-[13px] font-extrabold leading-snug text-[#19213D]">{{ __('dashboard.sidebar_promo_title') }}</p>
            <a href="{{ $createRoute }}"
               class="mt-3 inline-flex text-[12px] font-extrabold text-[#7138F5] no-underline hover:text-[#5825D6]">
                {{ __('dashboard.sidebar_promo_cta') }}
            </a>
        </div>
        @if ($needsIdentity)
            <a href="{{ route('identity.create') }}"
               class="mt-3 flex items-center gap-2 rounded-[11px] px-2 py-2 text-[12px] font-semibold text-orange-600 no-underline hover:bg-orange-50">
                <span class="h-2 w-2 rounded-full bg-orange-500" aria-hidden="true"></span>
                {{ __('dashboard.nav_identity') }}
            </a>
        @endif
    </div>
</aside>
