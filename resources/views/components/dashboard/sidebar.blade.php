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
    $exploreCategories = $categories->take(5);
    $isHome = request()->routeIs('dashboard');
    $isDiscover = request()->routeIs('events.index');
    $isMine = request()->routeIs('events.mine');
    $isChats = request()->routeIs('chats.*', 'events.chat', 'dms.*');
    $isFriends = request()->routeIs('friends.*');
    $isNotifications = request()->routeIs('notifications.*');
    $isFavorites = request()->routeIs('events.favorites');
    $isCreate = request()->routeIs('events.create');
    $isIdentity = request()->routeIs('identity.create');
    $isBlocked = request()->routeIs('blocks.index');
    $needsIdentity = $user?->needsIdentityDocuments() === true;
@endphp

<aside class="dash-sidebar relative min-h-screen pb-28">
    <a href="{{ route('dashboard') }}" class="mb-6 flex items-center gap-2.5 px-2.5 pb-1 no-underline">
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
               'bg-indigo-50 text-indigo-600' => $isHome,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isHome,
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
               'bg-indigo-50 text-indigo-600' => $isDiscover,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isDiscover,
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
               'bg-indigo-50 text-indigo-600' => $isMine,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isMine,
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
               'bg-indigo-50 text-indigo-600' => $isChats,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isChats,
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
               'bg-indigo-50 text-indigo-600' => $isFriends,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isFriends,
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
               'bg-indigo-50 text-indigo-600' => $isNotifications,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isNotifications,
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
               'bg-indigo-50 text-indigo-600' => $isFavorites,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isFavorites,
           ])
           @if ($isFavorites) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                <path d="M27 4v27a1 1 0 0 1-1.625.781L16 24.281l-9.375 7.5A1 1 0 0 1 5 31V4a4 4 0 0 1 4-4h14a4 4 0 0 1 4 4z"/>
            </svg>
            {{ __('dashboard.nav_favorites') }}
        </a>

        <a href="{{ $createRoute }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-indigo-50 text-indigo-600' => $isCreate,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isCreate,
           ])>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path stroke-linecap="round" d="M16 2v4M8 2v4M12 11v6M9 14h6"/>
            </svg>
            {{ __('dashboard.nav_create') }}
        </a>

        <a href="{{ route('identity.create') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-indigo-50 text-indigo-600' => $isIdentity,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isIdentity,
           ])
           @if ($isIdentity) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path stroke-linecap="round" d="M9 12l2 2 4-4"/>
            </svg>
            <span class="min-w-0 flex-1 truncate">{{ __('dashboard.nav_identity') }}</span>
            @if ($needsIdentity)
                <span class="h-2 w-2 shrink-0 rounded-full bg-orange-500" aria-hidden="true"></span>
            @endif
        </a>

        <a href="{{ route('blocks.index') }}"
           @class([
               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
               'bg-indigo-50 text-indigo-600' => $isBlocked,
               'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $isBlocked,
           ])
           @if ($isBlocked) aria-current="page" @endif>
            <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" d="M7 7l10 10"/>
            </svg>
            {{ __('dashboard.nav_blocked') }}
        </a>

        @if ($exploreCategories->isNotEmpty())
            <p class="px-3 pb-1.5 pt-4 text-[10px] font-extrabold uppercase tracking-[0.1em] text-slate-400">
                {{ __('dashboard.nav_explore') }}
            </p>

            @foreach ($exploreCategories as $category)
                <a href="{{ route('events.index', ['category_id' => $category->id, 'city_id' => '']) }}"
                   class="flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold text-slate-500 no-underline transition hover:bg-indigo-50 hover:text-indigo-600">
                    <span class="flex h-[17px] w-[17px] shrink-0 items-center justify-center text-sm leading-none">{{ category_emoji($category->localizedName()) }}</span>
                    <span class="min-w-0 flex-1 truncate">{{ $category->localizedName() }}</span>
                    @if (($category->published_events_count ?? 0) > 0)
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">{{ $category->published_events_count }}</span>
                    @endif
                </a>
            @endforeach
        @endif
    </nav>

    <a href="{{ route('settings.location.edit') }}"
       data-location-modal-open
       class="absolute inset-x-[0.95rem] bottom-[1.0625rem] block rounded-[14px] border border-[#dfe8fb] bg-[#f6f9ff] px-3 py-3 no-underline transition hover:border-indigo-200">
        <span class="flex items-center gap-2 text-[13px] font-bold text-slate-800">
            <svg class="h-4 w-4 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                <circle cx="12" cy="9" r="2.5"/>
            </svg>
            {{ $cityLabel }}
        </span>
        <small class="mt-1 block ps-6 text-[11px] text-slate-500">
            {{ __('dashboard.showing_within', ['km' => $distanceKm]) }}
        </small>
    </a>
</aside>
