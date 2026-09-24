@props([
    'user' => null,
    'cityName' => null,
    'when' => null,
    'categories',
    'publishedTotal' => 0,
    'highlight' => null,
    'stats' => ['events' => 0, 'going' => 0, 'left' => 0],
])

@php
    $firstName = $user ? explode(' ', trim($user->name))[0] : null;
    $liveBadge = $cityName
        ? __('design.live_badge', ['city' => $cityName])
        : __('design.live_badge_generic');
    $welcomeSub = $cityName
        ? __('design.welcome_sub_design', ['city' => $cityName])
        : __('design.welcome_sub_generic');
    $activeCategoryId = request('category_id');
    $dateChips = [
        'today' => __('home.today'),
        'tomorrow' => __('home.tomorrow'),
        'weekend' => __('home.weekend'),
    ];
    $statLabels = [
        'events' => __('design.stat_events'),
        'going' => __('design.stat_going'),
        'left' => __('design.stat_left'),
    ];

    $hlTitle = '7-a-side Friendly Match';
    $hlVenue = 'Obour Sporting Club · Pitch 4';
    $hlTime = 'Tonight · 9:00 PM';
    $hlSpots = 3;
    $hlImage = asset('images/activities/football-640.webp');
    $hlCoverEvent = null;
    $hlLink = route('events.index');
    $hlGoing = 7;

    if ($highlight) {
        $hlTitle = $highlight->title;
        $hlVenue = $highlight->venue . ($highlight->city ? ' · ' . $highlight->city->localizedName() : '');
        $hlTime = $highlight->starts_at?->timezone(config('app.timezone'))->translatedFormat('l · g:i A') ?? '';
        $hlSpots = $highlight->remainingCapacity();
        $hlImage = $highlight->coverUrl('card');
        $hlCoverEvent = $highlight;
        $hlLink = event_show_url($highlight);
        $hlGoing = $highlight->acceptedMembersCount();
    } elseif ($user?->canParticipate()) {
        $hlLink = route('events.create');
    }
@endphp

<section class="pt-6 sm:pt-8">
    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-indigo-700 via-violet-700 to-indigo-900 shadow-2xl shadow-indigo-600/25">
        <div aria-hidden="true" class="absolute inset-0">
            <div class="absolute -top-32 -end-24 h-96 w-96 rounded-full bg-orange-400/25 blur-3xl"></div>
            <div class="absolute -bottom-40 -start-20 h-96 w-96 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
            <div class="bg-dots absolute inset-0 opacity-60"></div>
            <span class="animate-float absolute end-[26rem] top-10 hidden text-5xl drop-shadow-xl xl:block" style="--float-rot: 12deg">⚽</span>
            <span class="animate-float absolute end-8 bottom-40 hidden text-4xl opacity-90 drop-shadow-xl lg:block" style="--float-rot: -10deg; animation-delay: -2s">♟️</span>
            <span class="animate-float absolute start-[30rem] top-8 hidden text-4xl opacity-80 drop-shadow-xl lg:block" style="--float-rot: 8deg; animation-delay: -4s">🎾</span>
        </div>

        <div class="relative grid gap-8 p-6 sm:p-10 lg:grid-cols-[1fr_340px] lg:p-12">
            <div class="flex flex-col gap-6">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-white backdrop-blur">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        </span>
                        {{ $liveBadge }}
                    </span>
                </div>

                <div>
                    <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-[2.75rem]">
                        @if ($user)
                            {{ __('design.welcome') }}
                            <span class="bg-gradient-to-r from-amber-300 to-orange-400 bg-clip-text text-transparent">
                                {{ $firstName }}
                            </span>
                        @else
                            {{ __('design.welcome_guest') }}
                        @endif
                        <span class="inline-block origin-bottom-right hover:rotate-12">👋</span>
                    </h1>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-indigo-100/90 sm:text-base">
                        {{ $welcomeSub }}
                    </p>
                </div>

                <div
                    class="live-search relative max-w-xl"
                    data-live-search
                    data-live-search-type="events"
                    data-live-search-url="{{ route('search.suggest') }}"
                    data-live-search-empty="{{ __('ui.search_empty') }}"
                    data-live-search-see-all="{{ __('ui.search_see_all') }}"
                    data-live-search-error="{{ __('ui.search_failed') }}"
                >
                    <form method="GET" action="{{ route('events.index') }}" class="flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 p-2 backdrop-blur-xl transition focus-within:border-white/40 focus-within:bg-white/15">
                        <svg class="ms-2 h-5 w-5 shrink-0 text-indigo-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path stroke-linecap="round" d="M20 20l-3-3"/>
                        </svg>
                        <input type="search" name="q" value="{{ request('q') }}"
                               placeholder="{{ __('design.search_ph') }}"
                               aria-label="{{ __('design.search_ph') }}"
                               autocomplete="off"
                               data-live-search-input
                               class="min-w-0 flex-1 bg-transparent text-sm text-white outline-none placeholder:text-indigo-200/70">
                        <button type="submit" class="flex h-10 shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 text-sm font-bold text-white shadow-lg shadow-orange-900/30 transition hover:brightness-110 active:scale-95">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                            </svg>
                            <span class="hidden sm:inline">{{ __('design.search_cta') }}</span>
                        </button>
                    </form>
                    <div class="live-search__panel live-search__panel--on-dark" data-live-search-panel hidden>
                        <div class="live-search__searching" data-live-search-searching hidden>
                            <x-ui.searching size="sm" />
                        </div>
                        <div class="live-search__results" data-live-search-results></div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('design.pick_date') }}">
                    @foreach ($dateChips as $chipId => $chipLabel)
                        @php $active = $when === $chipId; @endphp
                        <a href="{{ route('home', ['when' => $chipId]) }}"
                           role="tab"
                           aria-selected="{{ $active ? 'true' : 'false' }}"
                           @class([
                               'flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-xs font-bold text-indigo-700 shadow-lg' => $active,
                               'flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold text-indigo-50 backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/20' => ! $active,
                           ])>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" d="M12 7v5l3 2"/>
                            </svg>
                            {{ $chipLabel }}
                        </a>
                    @endforeach
                    <a href="{{ route('events.index') }}"
                       role="tab"
                       aria-selected="false"
                       class="flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold text-indigo-50 backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/20">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                        {{ __('design.pick_date') }}
                    </a>
                </div>

                <div class="grid max-w-xl grid-cols-3 gap-3">
                    @foreach ($statLabels as $key => $label)
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur transition hover:border-white/30 hover:bg-white/15">
                            <div class="text-2xl font-extrabold text-white">{{ number_format($stats[$key] ?? 0) }}</div>
                            <div class="mt-0.5 text-[11px] font-medium leading-tight text-indigo-200">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="flex flex-col gap-4 rounded-3xl border border-white/20 bg-white/10 p-5 backdrop-blur-xl">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73 0-2.15.74-4.8.74-4.8S10.5 0 12 0s1.5.67 1.5.67zM11.25 22.5h1.5V14h-1.5v8.5z"/>
                        </svg>
                        {{ __('design.hl_label') }}
                    </span>
                    <span class="rounded-full bg-orange-500 px-2.5 py-1 text-[10px] font-black text-white shadow-lg shadow-orange-900/40">
                        {{ __('design.hl_spots', ['count' => $hlSpots]) }}
                    </span>
                </div>

                <div class="relative overflow-hidden rounded-2xl">
                    @if ($hlCoverEvent)
                        <x-events.cover-img
                            :event="$hlCoverEvent"
                            size="card"
                            priority
                            :alt="$hlTitle"
                            class="h-36 w-full object-cover"
                        />
                    @else
                        <img
                            src="{{ $hlImage }}"
                            alt="{{ $hlTitle }}"
                            width="640"
                            height="360"
                            fetchpriority="high"
                            decoding="async"
                            class="h-36 w-full object-cover"
                        >
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-indigo-950/80 to-transparent"></div>
                    <div class="absolute bottom-3 start-3 end-3">
                        <p class="text-base font-extrabold leading-snug text-white">{{ $hlTitle }}</p>
                        <p class="mt-0.5 flex items-center gap-1 text-[11px] font-medium text-indigo-200">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                            </svg>
                            {{ $hlVenue }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-indigo-100">
                    <span class="flex items-center gap-1.5 font-semibold">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" d="M12 7v5l3 2"/>
                        </svg>
                        {{ $hlTime }}
                    </span>
                    <span class="flex -space-x-2 rtl:space-x-reverse">
                        @foreach (['YM', 'AS', 'MA'] as $idx => $initial)
                            <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-indigo-800 bg-gradient-to-br from-indigo-400 to-violet-500 text-[9px] font-bold text-white" style="z-index: {{ 3 - $idx }}">{{ $initial }}</span>
                        @endforeach
                        @if ($hlGoing > 3)
                            <span class="flex h-6 items-center rounded-full border-2 border-indigo-800 bg-white/20 px-1.5 text-[9px] font-bold text-white">+{{ $hlGoing - 3 }}</span>
                        @endif
                    </span>
                </div>

                <a href="{{ $hlLink }}" class="mt-auto flex w-full items-center justify-center gap-2 rounded-xl bg-white py-3 text-sm font-extrabold text-indigo-700 shadow-xl transition hover:scale-[1.02] hover:bg-indigo-50 active:scale-95">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                    {{ __('design.hl_cta') }}
                </a>
            </aside>
        </div>
    </div>

    <div class="fancy-scroll mt-6 flex gap-2.5 overflow-x-auto pb-2" role="toolbar" aria-label="{{ __('home.all_activities') }}">
        <a href="{{ route('events.index') }}"
           @class([
               'flex shrink-0 items-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition-all' => ! $activeCategoryId,
               'flex shrink-0 items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-600 backdrop-blur transition-all hover:-translate-y-0.5 hover:border-indigo-300 hover:text-indigo-700 hover:shadow-md' => $activeCategoryId,
           ])>
            <span class="text-base leading-none">✨</span>
            {{ __('home.all_activities') }}
            <span @class([
                'rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                'bg-white/25 text-white' => ! $activeCategoryId,
                'bg-slate-100 text-slate-500' => $activeCategoryId,
            ])>{{ $publishedTotal }}</span>
        </a>
        @foreach ($categories as $category)
            @php
                $active = (string) $activeCategoryId === (string) $category->id;
                $emoji = category_emoji($category->localizedName());
                $count = $category->published_events_count ?? 0;
            @endphp
            <a href="{{ route('events.index', ['category_id' => $category->id, 'city_id' => '']) }}"
               aria-pressed="{{ $active ? 'true' : 'false' }}"
               @class([
                   'flex shrink-0 items-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition-all' => $active,
                   'flex shrink-0 items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-600 backdrop-blur transition-all hover:-translate-y-0.5 hover:border-indigo-300 hover:text-indigo-700 hover:shadow-md' => ! $active,
               ])>
                <span class="text-base leading-none">{{ $emoji }}</span>
                {{ $category->localizedName() }}
                <span @class([
                    'rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                    'bg-white/25 text-white' => $active,
                    'bg-slate-100 text-slate-500' => ! $active,
                ])>{{ $count }}</span>
            </a>
        @endforeach
    </div>
</section>
