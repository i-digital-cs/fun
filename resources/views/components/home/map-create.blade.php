@props([
    'user' => null,
    'eventsCount' => 0,
    'publishedTotal' => 0,
])

@php
    $cityName = $user?->city?->localizedName();
    $radius = $user?->distance_km;
    $canCreate = $user?->canParticipate() === true;
    $mapTitle = $cityName
        ? __('home.map_title', ['city' => $cityName])
        : __('home.map_title_generic');
@endphp

<section class="mb-8" aria-labelledby="home-map-heading">
    <div class="grid gap-4 lg:grid-cols-12">
        <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm lg:col-span-8">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 10.9a2.1 2.1 0 100-4.2 2.1 2.1 0 000 4.2zM12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                    </span>
                    <h2 id="home-map-heading" class="text-base font-semibold text-text">{{ $mapTitle }}</h2>
                </div>
                @if ($radius)
                    <span class="rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-semibold text-primary-ink">
                        {{ __('home.map_radius', ['km' => $radius]) }}
                    </span>
                @endif
            </div>

            <div class="relative mb-3 flex min-h-[14rem] items-end overflow-hidden rounded-lg bg-cover bg-center p-4 text-white"
                 style="background-image:linear-gradient(to top, rgba(40,48,68,.85), transparent 55%), url('https://lh3.googleusercontent.com/aida-public/AB6AXuAYInusDdZZbeWjRUTAqU-44F8QB49CoRc2lZ4uhVaESvW22cGGEWixynaT6dhBm9EHoOwn5b2Sc_MV5FPhL6e0tQMEnLW6uGWAEYChi3nse7zcix21EFsRSJ_yc-Q3b3qmJC3yLPMcKgG5F2piFML5qH-ujFAOpQDO96NKVl0LFkd-_qoaJD25WvE9HfdFxGvEWH5IDlSsFbx5-Rcsf6bl8a9p8zuwHUSVZ_ol9AHe')">
                <div class="relative z-[1] flex w-full items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $cityName ? __('home.map_hub', ['city' => $cityName]) : __('home.map_hub_generic') }}</p>
                        <p class="text-sm text-white/75">{{ trans_choice('home.map_active_hubs', $eventsCount, ['count' => $eventsCount]) }}</p>
                    </div>
                    <a href="{{ route('events.index') }}" class="rounded-lg bg-surface px-3 py-1.5 text-sm font-medium text-text shadow-sm hover:bg-background">
                        {{ __('home.expand_radar') }}
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-background p-2">
                    <div class="text-lg font-bold text-primary">{{ $publishedTotal }}</div>
                    <div class="text-xs text-muted">{{ __('home.stat_events_listed') }}</div>
                </div>
                <div class="rounded-xl bg-background p-2">
                    <div class="text-lg font-bold text-success">{{ $eventsCount }}</div>
                    <div class="text-xs text-muted">{{ __('home.stat_nearby_now') }}</div>
                </div>
                <div class="rounded-xl bg-background p-2">
                    <div class="text-lg font-bold text-accent">{{ $publishedTotal }}</div>
                    <div class="text-xs text-muted">{{ __('home.stat_this_week') }}</div>
                </div>
            </div>
        </div>

        <div class="flex flex-col rounded-2xl border border-border bg-surface p-5 shadow-sm lg:col-span-4">
            <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-accent" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            </div>
            <h3 class="text-base font-semibold text-text">{{ __('home.gather_title') }}</h3>
            <p class="mt-2 text-sm text-muted">{{ __('home.gather_body') }}</p>
            <ul class="mt-4 space-y-1.5 text-sm text-muted">
                <li class="flex items-start gap-1.5">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-success" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    {{ __('home.gather_point_screen') }}
                </li>
                <li class="flex items-start gap-1.5">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-success" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    {{ __('home.gather_point_count') }}
                </li>
                <li class="flex items-start gap-1.5">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-success" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    {{ __('home.gather_point_chat') }}
                </li>
            </ul>
            <div class="mt-auto pt-5">
                @if ($canCreate)
                    <a href="{{ route('events.create') }}" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        {{ __('home.create_an_event') }}
                    </a>
                @elseif ($user === null)
                    <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                        {{ __('home.create_an_event') }}
                    </a>
                @elseif ($user->needsIdentityDocuments())
                    <a href="{{ route('identity.create') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                        {{ __('identity.title') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                        {{ __('home.create_an_event') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
