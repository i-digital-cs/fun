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
    $eventItems = collect($events ?? [])->take(8);
    $worthItems = collect($worthTheTrip ?? [])->take(4);
    $selectedCityId = $user?->city_id;
    $selectedDistance = $distanceKm ?? config('location.default_distance_km');
    $nearbySub = $cityName
        ? __('dashboard.nearby_sub', [
            'city' => $cityName,
            'km' => (int) $selectedDistance === 0 ? __('location.all_distances') : $selectedDistance,
        ])
        : __('dashboard.nearby_sub_generic');
    $heroImage = asset('assets/funnity-v2/hero/hero-cafe-interior-1280.webp');
@endphp

{{-- lg+: three-column social shell (sidebar is layout; this is main + rail) --}}
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_17.5rem] lg:items-start">
    <div class="min-w-0">
        {{-- Social hero --}}
        <section class="dash-hero-v3 relative overflow-hidden rounded-[1.35rem] text-white shadow-[0_24px_48px_-28px_rgba(25,33,61,0.55)]"
                 style="background-image: linear-gradient(105deg, rgba(17,12,48,0.88) 0%, rgba(49,24,110,0.62) 38%, rgba(17,12,48,0.18) 72%, rgba(17,12,48,0.05) 100%), url('{{ $heroImage }}'); background-size: cover; background-position: center;">
            <div class="relative z-10 px-5 py-8 sm:px-8 sm:py-10 lg:max-w-[42rem]">
                <p class="mb-2 text-sm font-semibold text-white/85">
                    {{ __($greetingKey, ['name' => $firstName]) }} 👋
                </p>
                <h1 class="text-[clamp(1.75rem,3.6vw,2.75rem)] font-extrabold leading-[1.05] tracking-tight">
                    {{ __('dashboard.hero_title_1') }}<br>{{ __('dashboard.hero_title_2') }}
                </h1>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-white/80">
                    {{ __('dashboard.hero_body') }}
                </p>

                <form method="GET" action="{{ route('events.index') }}"
                      class="mt-5 flex flex-col gap-2 rounded-[1rem] bg-white p-2 shadow-xl sm:flex-row sm:items-stretch">
                    <div
                        class="live-search relative min-w-0 flex-1"
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
                               placeholder="{{ __('dashboard.mood_placeholder') }}"
                               aria-label="{{ __('dashboard.mood_placeholder') }}"
                               autocomplete="off"
                               data-live-search-input
                               class="min-w-0 w-full rounded-xl border-0 bg-[#F7F9FC] px-3.5 py-3 text-sm text-slate-700 outline-none placeholder:text-slate-400">
                        <div class="live-search__panel" data-live-search-panel hidden>
                            <div class="live-search__searching" data-live-search-searching hidden>
                                <x-ui.searching size="sm" />
                            </div>
                            <div class="live-search__results" data-live-search-results></div>
                        </div>
                    </div>

                    <select name="city_id"
                            aria-label="{{ __('events.city') }}"
                            class="w-full shrink-0 rounded-xl border-0 bg-[#F7F9FC] px-3 py-3 text-sm text-slate-700 outline-none sm:w-[9.75rem]">
                        <option value="">{{ __('events.all_cities') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((int) $selectedCityId === (int) $city->id)>
                                {{ $city->localizedName() }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#7138F5] px-4 py-3 text-sm font-extrabold whitespace-nowrap text-white transition hover:bg-[#5825D6]">
                        {{ __('dashboard.find_events') }}
                    </button>
                </form>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($categories->take(7) as $category)
                        <a href="{{ route('events.index', ['category_id' => $category->id]) }}"
                           class="rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white no-underline backdrop-blur transition hover:bg-white/20">
                            {{ category_emoji($category->localizedName()) }} {{ $category->localizedName() }}
                        </a>
                    @endforeach
                    <a href="{{ route('events.index') }}"
                       class="rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-[11px] font-bold text-white no-underline backdrop-blur transition hover:bg-white/20">
                        {{ __('dashboard.more_categories') }}
                    </a>
                </div>
            </div>
        </section>

        <x-dashboard.home-mini-map
            :city-name="$cityName"
            :distance-km="$selectedDistance"
            :has-nearby-origin="$hasNearbyOrigin"
            :latitude="$miniMapLat ?? null"
            :longitude="$miniMapLng ?? null"
            :discovery-mode="$miniMapMode ?? 'nationwide'"
            :city-id="$miniMapCityId ?? null"
            :suggested-events="$eventItems"
            :expanded="(bool) ($discoveryExpanded ?? false)"
        />

        {{-- Events near you --}}
        <section class="mt-7" id="nearby">
            <div class="mb-3.5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-[1.15rem] font-extrabold tracking-tight text-[#19213D]">{{ __('dashboard.events_near_you') }}</h2>
                    <p class="mt-0.5 text-[12px] text-slate-500">{{ $nearbySub }}</p>
                </div>
                <a href="{{ route('events.index') }}" class="text-xs font-bold text-[#7138F5] no-underline hover:text-[#5825D6]">
                    {{ __('dashboard.see_all') }}
                </a>
            </div>

            @if (!empty($discoveryExpanded) && !empty($discoveryMeta))
                <p class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('events.expanded_notice', [
                        'requested' => (int) ($discoveryMeta['requested_radius_km'] ?? 0) === 0
                            ? __('location.all_distances')
                            : ((int) $discoveryMeta['requested_radius_km'].' '.__('location.km')),
                        'effective' => (int) ($discoveryMeta['effective_radius_km'] ?? 0) === 0
                            ? __('location.all_distances')
                            : ((int) $discoveryMeta['effective_radius_km'].' '.__('location.km')),
                    ]) }}
                </p>
            @elseif ($nationwideFallback)
                <p class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ __('events.nationwide_notice') }}</p>
            @endif

            @if ($eventItems->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                    {{ __('dashboard.empty_nearby') }}
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">
                    @foreach ($eventItems->take(4) as $event)
                        <x-dashboard.social-event-card
                            :event="$event"
                            :join-action="$joinActionsById[$event->id] ?? null"
                            :favorited="isset($favoritedIds[$event->id])"
                        />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- People --}}
        <div class="mt-8">
            <x-community.people-suggestions :suggestions="$peopleSuggestions ?? []" />
        </div>

        {{-- Worth the trip --}}
        @if ($worthItems->isNotEmpty())
            <section class="mt-8">
                <div class="mb-3.5">
                    <h2 class="text-[1.15rem] font-extrabold tracking-tight text-[#19213D]">{{ __('events.worth_the_trip_title') }}</h2>
                    <p class="mt-0.5 text-[12px] text-slate-500">{{ __('events.worth_the_trip_sub') }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($worthItems as $event)
                        <x-dashboard.social-event-card
                            :event="$event"
                            :join-action="$joinActionsById[$event->id] ?? null"
                            :favorited="isset($favoritedIds[$event->id])"
                        />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Right contextual rail (desktop/tablet landscape); stacks under main on small screens --}}
    <aside class="min-w-0 lg:sticky lg:top-[5.25rem] lg:self-start" aria-label="{{ __('dashboard.rail_label') }}">
        <x-dashboard.social-rail
            :user="$user"
            :city-name="$cityName"
            :distance-km="$selectedDistance"
            :memberships="$memberships"
            :pending-requests="$pendingRequests"
            :now-playing="$nowPlaying"
            :create-route="$createRoute"
        />
    </aside>
</div>
@endsection
