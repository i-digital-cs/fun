@extends('layouts.dashboard')

@section('title', $appName)

@section('content')
@php
    $firstName = filled($user?->name) ? explode(' ', trim($user->name))[0] : '';
    $hour = now()->hour;
    $greetingKey = match (true) {
        $hour < 12 => 'dashboard.greeting_morning',
        $hour < 17 => 'dashboard.greeting_afternoon',
        default => 'dashboard.greeting_evening',
    };
    $canParticipate = $user?->canParticipate() === true;
    $createRoute = $canParticipate ? route('events.create') : route('identity.create');
    $eventItems = $events->getCollection();
    $selectedCityId = $user?->city_id;
    $selectedDistance = $distanceKm ?? config('location.default_distance_km');
    $nearbySub = $cityName
        ? __('dashboard.nearby_sub', ['city' => $cityName, 'km' => $selectedDistance])
        : __('dashboard.nearby_sub_generic');
@endphp

{{-- Greeting --}}
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[25px] font-extrabold tracking-tight text-slate-900 sm:text-[25px]">
            {{ __($greetingKey, ['name' => $firstName]) }} 👋
        </h1>
        <p class="mt-1 text-[13px] text-slate-500">{{ __('dashboard.greeting_sub') }}</p>
    </div>
    <a href="{{ $createRoute }}"
       class="inline-flex items-center gap-1.5 rounded-[11px] bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-orange-500/25 transition hover:-translate-y-0.5 hover:shadow-xl">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
        {{ __('events.create_event') }}
    </a>
</div>

{{-- Hero --}}
<section class="dash-hero relative flex min-h-[315px] items-center overflow-hidden rounded-[23px] px-6 py-8 text-white sm:px-10 sm:py-10">
    <x-ui.espiral tone="amber" size="lg" :arms="3" :dots="14" class="espiral--anchor-end" />
    <div class="relative z-10 max-w-[650px]">
        <span class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-2.5 py-1.5 text-[11px] font-bold">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
            </svg>
            {{ __('dashboard.hero_eyebrow') }}
        </span>

        <h2 class="mb-3.5 text-[clamp(2rem,4.2vw,3.625rem)] font-extrabold leading-[1.01] tracking-tight">
            {{ __('dashboard.hero_title_1') }}<br>{{ __('dashboard.hero_title_2') }}
        </h2>

        <p class="mb-5 max-w-[580px] text-sm leading-relaxed text-blue-100">
            {{ __('dashboard.hero_body') }}
        </p>

        <form method="GET" action="{{ route('events.index') }}"
              class="grid max-w-[760px] gap-1.5 rounded-[15px] bg-white p-2 shadow-[0_20px_50px_rgba(0,0,0,0.2)] sm:grid-cols-[145px_150px_1fr_auto]">
            <select name="city_id"
                    aria-label="{{ __('events.city') }}"
                    class="min-w-0 rounded-[10px] border-0 bg-[#f7f9fc] px-3 py-3 text-xs text-slate-700 outline-none">
                <option value="">{{ __('events.all_cities') }}</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected((int) $selectedCityId === (int) $city->id)>
                        {{ $city->localizedName() }}
                    </option>
                @endforeach
            </select>

            <select name="distance_km"
                    aria-label="{{ __('location.discovery_distance') }}"
                    class="min-w-0 rounded-[10px] border-0 bg-[#f7f9fc] px-3 py-3 text-xs text-slate-700 outline-none">
                @foreach ($distances as $km)
                    <option value="{{ $km }}" @selected((int) $selectedDistance === (int) $km)>
                        {{ __('dashboard.within_km', ['km' => $km]) }}
                    </option>
                @endforeach
            </select>

            <div
                class="live-search relative min-w-0 sm:col-span-1"
                data-live-search
                data-live-search-type="events"
                data-live-search-url="{{ route('search.suggest') }}"
                data-live-search-empty="{{ __('ui.search_empty') }}"
                data-live-search-see-all="{{ __('ui.search_see_all') }}"
                data-live-search-error="{{ __('ui.search_failed') }}"
            >
                <input type="search"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="{{ __('dashboard.hero_activity_ph') }}"
                       aria-label="{{ __('dashboard.hero_activity_ph') }}"
                       autocomplete="off"
                       data-live-search-input
                       class="min-w-0 w-full rounded-[10px] border-0 bg-[#f7f9fc] px-3 py-3 text-xs text-slate-700 outline-none placeholder:text-slate-400">
                <div class="live-search__panel" data-live-search-panel hidden>
                    <div class="live-search__searching" data-live-search-searching hidden>
                        <x-ui.searching size="sm" />
                    </div>
                    <div class="live-search__results" data-live-search-results></div>
                </div>
            </div>

            <button type="submit"
                    class="inline-flex items-center justify-center gap-1.5 rounded-[10px] bg-indigo-600 px-4 py-3 text-xs font-bold text-white transition hover:bg-indigo-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" d="M20 20l-3-3"/>
                </svg>
                {{ __('dashboard.find_events') }}
            </button>
        </form>

        <div class="mt-4 flex flex-wrap gap-4 text-[11px] text-blue-100">
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path stroke-linecap="round" d="M9 12l2 2 4-4"/>
                </svg>
                {{ __('dashboard.trust_identity') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path stroke-linecap="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
                {{ __('dashboard.trust_people') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                    <path stroke-linecap="round" d="M4 22v-7"/>
                </svg>
                {{ __('dashboard.trust_report') }}
            </span>
        </div>
    </div>
</section>

{{-- Your participations: joined + pending --}}
@if ($user?->canParticipate())
    <div class="mt-8">
        <x-community.participations
            :memberships="$memberships"
            :pending-requests="$pendingRequests"
            :allow-demo="false"
        />
    </div>
@endif

{{-- Categories --}}
<section class="mt-8">
    <div class="mb-3.5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-[19px] font-extrabold tracking-tight text-slate-900">{{ __('dashboard.into_title') }}</h2>
            <p class="mt-0.5 text-[11px] text-slate-500">{{ __('dashboard.into_sub') }}</p>
        </div>
        <a href="{{ route('events.index') }}" class="text-xs font-bold text-indigo-600 no-underline hover:text-indigo-700">
            {{ __('dashboard.view_all_categories') }}
        </a>
    </div>

    <div class="grid grid-cols-4 gap-2.5 md:grid-cols-4 xl:grid-cols-8">
        @foreach ($categories as $category)
            <a href="{{ route('events.index', ['category_id' => $category->id, 'city_id' => '']) }}"
               class="rounded-[15px] border border-[#e7ebf3] bg-white px-1.5 py-4 text-center text-slate-700 no-underline transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-[0_12px_35px_rgba(15,23,42,0.06)]">
                <span class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-full bg-indigo-50 text-xl">{{ category_emoji($category->localizedName()) }}</span>
                <b class="block text-[11px] font-bold leading-tight">{{ $category->localizedName() }}</b>
                <span class="mt-1 block text-[10px] font-semibold text-slate-400">{{ $category->published_events_count ?? 0 }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- Nearby events --}}
<section class="mt-8" id="nearby">
    <div class="mb-3.5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-[19px] font-extrabold tracking-tight text-slate-900">{{ __('dashboard.nearby_title') }}</h2>
            <p class="mt-0.5 text-[11px] text-slate-500">{{ $nearbySub }}</p>
        </div>
        <a href="{{ route('events.index') }}" class="text-xs font-bold text-indigo-600 no-underline hover:text-indigo-700">
            {{ __('dashboard.see_all') }}
        </a>
    </div>

    @if ($nationwideFallback)
        <p class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ __('events.nationwide_notice') }}</p>
    @endif

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_19.375rem]">
        <div>
            @if ($eventItems->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                    {{ __('dashboard.empty_nearby') }}
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($eventItems as $event)
                        <x-dashboard.event-card
                            :event="$event"
                            :join-action="$joinActionsById[$event->id] ?? null"
                            :favorited="isset($favoritedIds[$event->id])"
                        />
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="mt-4 grid gap-4 sm:grid-cols-2 xl:mt-0 xl:grid-cols-1">
            <div class="flex justify-center xl:justify-start">
                <x-dashboard.weather-card :weather="$weather" />
            </div>

            <x-dashboard.now-playing
                :running="$nowPlaying['running'] ?? null"
                :next="$nowPlaying['next'] ?? []"
                :previous="$nowPlaying['previous'] ?? []"
            />

            {{-- Starting soon --}}
            <div class="rounded-[17px] border border-[#e7ebf3] bg-white p-4">
                <h3 class="mb-3 text-sm font-extrabold text-slate-900">{{ __('dashboard.starting_soon') }}</h3>
                @forelse ($startingSoon as $soonEvent)
                    @php
                        $soonCategory = $soonEvent->category?->localizedName() ?? '';
                        $soonStarts = $soonEvent->starts_at?->timezone(config('app.timezone'));
                        $soonTime = $soonStarts
                            ? ($soonStarts->isToday()
                                ? __('events.today') . ' · ' . $soonStarts->translatedFormat('g:i A')
                                : ($soonStarts->isTomorrow()
                                    ? __('events.tomorrow') . ' · ' . $soonStarts->translatedFormat('g:i A')
                                    : $soonStarts->translatedFormat('D · M j · g:i A')))
                            : '';
                    @endphp
                    <a href="{{ route('events.show', $soonEvent) }}"
                       class="flex items-center gap-2.5 border-b border-slate-100 py-2.5 last:border-b-0 no-underline hover:bg-slate-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-indigo-50 text-base">{{ category_emoji($soonCategory) }}</span>
                        <span>
                            <b class="block text-[11px] font-bold text-slate-800">{{ $soonEvent->title }}</b>
                            <span class="text-[10px] text-slate-500">{{ $soonTime }}</span>
                        </span>
                    </a>
                @empty
                    <p class="text-[11px] text-slate-500">{{ __('dashboard.empty_nearby') }}</p>
                @endforelse
            </div>

            {{-- Safety --}}
            <div class="rounded-[17px] border-0 bg-gradient-to-br from-indigo-700 to-indigo-600 p-4 text-white">
                <span class="mb-3 flex h-11 w-11 items-center justify-center rounded-[13px] bg-white/15">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path stroke-linecap="round" d="M9 12l2 2 4-4"/>
                    </svg>
                </span>
                <h3 class="mb-2 text-[15px] font-extrabold">{{ __('dashboard.safety_title') }}</h3>
                <p class="mb-3 text-[11px] leading-relaxed text-blue-100">{{ __('dashboard.safety_body') }}</p>
                <a href="{{ route('safety.guidelines') }}"
                   class="inline-block rounded-[9px] bg-white px-3 py-2 text-[11px] font-extrabold text-indigo-700 no-underline transition hover:bg-blue-50">
                    {{ __('dashboard.safety_cta') }}
                </a>
            </div>
        </aside>
    </div>
</section>

{{-- How it works --}}
<section class="mt-8">
    <div class="mb-3.5">
        <h2 class="text-[19px] font-extrabold tracking-tight text-slate-900">{{ __('dashboard.how_title', ['brand' => site_display_name()]) }}</h2>
        <p class="mt-0.5 text-[11px] text-slate-500">{{ __('dashboard.how_sub') }}</p>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        @foreach ([
            ['step' => '01', 'title' => __('dashboard.how_1_title'), 'body' => __('dashboard.how_1_body')],
            ['step' => '02', 'title' => __('dashboard.how_2_title'), 'body' => __('dashboard.how_2_body')],
            ['step' => '03', 'title' => __('dashboard.how_3_title'), 'body' => __('dashboard.how_3_body')],
        ] as $item)
            <div class="flex gap-3 rounded-2xl border border-[#e7ebf3] bg-white p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[11px] bg-indigo-50 text-xs font-extrabold text-indigo-600">{{ $item['step'] }}</span>
                <div>
                    <h4 class="mb-1 text-[13px] font-extrabold text-slate-900">{{ $item['title'] }}</h4>
                    <p class="text-[11px] leading-relaxed text-slate-500">{{ $item['body'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- CTA banner --}}
<section class="mt-8 flex flex-wrap items-center justify-between gap-5 rounded-[20px] border border-[#dce7ff] bg-gradient-to-r from-[#eff5ff] to-[#f5f3ff] px-6 py-7 sm:px-8">
    <div>
        <h2 class="text-[23px] font-extrabold tracking-tight text-slate-900">{{ __('dashboard.cta_title') }}</h2>
        <p class="mt-1 text-xs text-slate-500">{{ __('dashboard.cta_body') }}</p>
    </div>
    <a href="{{ $createRoute }}"
       class="inline-flex shrink-0 items-center gap-1.5 rounded-[11px] bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-orange-500/25 no-underline transition hover:-translate-y-0.5">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <path stroke-linecap="round" d="M16 2v4M8 2v4M12 11v6M9 14h6"/>
        </svg>
        {{ __('dashboard.cta_button') }}
    </a>
</section>
@endsection
