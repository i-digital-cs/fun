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
    $isCreate = request()->routeIs('events.create', 'identity.create');
@endphp

<aside class="dash-sidebar relative min-h-screen pb-28">
    <a href="{{ route('dashboard') }}" class="mb-6 flex items-center gap-2.5 px-2.5 pb-1 no-underline">
        <span class="flex h-10 w-10 items-center justify-center rounded-[13px] bg-gradient-to-br from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/25">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
            </svg>
        </span>
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
