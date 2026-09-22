@php
    $user = auth()->user();
    $canParticipate = $user?->canParticipate() === true;
    $createRoute = $canParticipate ? route('events.create') : route('identity.create');
    $isHome = request()->routeIs('dashboard');
    $isDiscover = request()->routeIs('events.index');
    $isMine = request()->routeIs('events.mine');
    $isCreate = request()->routeIs('events.create', 'identity.create');
    $isProfile = request()->routeIs('settings.location.edit');
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 flex h-[65px] items-center justify-around border-t border-[#e7ebf3] bg-white px-2 pt-1.5 max-lg:flex lg:hidden"
     aria-label="{{ __('ui.primary_nav') }}">
    <a href="{{ route('dashboard') }}"
       @class([
           'flex flex-col items-center gap-0.5 text-[9px] font-semibold no-underline',
           'font-extrabold text-indigo-600' => $isHome,
           'text-slate-500' => ! $isHome,
       ])
       @if ($isHome) aria-current="page" @endif>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/>
        </svg>
        {{ __('dashboard.nav_home') }}
    </a>

    <a href="{{ route('events.index') }}"
       @class([
           'flex flex-col items-center gap-0.5 text-[9px] font-semibold no-underline',
           'font-extrabold text-indigo-600' => $isDiscover,
           'text-slate-500' => ! $isDiscover,
       ])
       @if ($isDiscover) aria-current="page" @endif>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" d="M12 3v3M12 18v3M3 12h3M18 12h3"/>
        </svg>
        {{ __('dashboard.nav_discover') }}
    </a>

    <a href="{{ $createRoute }}"
       @class([
           'flex flex-col items-center gap-0.5 text-[9px] font-semibold no-underline',
           'font-extrabold text-indigo-600' => $isCreate,
           'text-slate-500' => ! $isCreate,
       ])>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" d="M12 8v8M8 12h8"/>
        </svg>
        {{ __('events.create_event_cta') }}
    </a>

    <a href="{{ route('events.mine') }}"
       @class([
           'flex flex-col items-center gap-0.5 text-[9px] font-semibold no-underline',
           'font-extrabold text-indigo-600' => $isMine,
           'text-slate-500' => ! $isMine,
       ])
       @if ($isMine) aria-current="page" @endif>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
        </svg>
        {{ __('dashboard.nav_my_events') }}
    </a>

    <a href="{{ route('settings.location.edit') }}"
       data-location-modal-open
       @class([
           'flex flex-col items-center gap-0.5 text-[9px] font-semibold no-underline',
           'font-extrabold text-indigo-600' => $isProfile,
           'text-slate-500' => ! $isProfile,
       ])
       @if ($isProfile) aria-current="page" @endif>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="8" r="4"/>
            <path stroke-linecap="round" d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"/>
        </svg>
        {{ __('dashboard.nav_profile') }}
    </a>
</nav>
