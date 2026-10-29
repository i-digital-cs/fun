@props([
    'mode' => 'preview', // home|discover|public|preview
    'styleUrl' => null,
    'attribution' => null,
    'showBanner' => false,
    'showTourControls' => true,
    'eventsUrl' => null,
    'latitude' => null,
    'longitude' => null,
    'distanceKm' => null,
    'discoveryMode' => 'nationwide',
    'locationLabel' => null,
    'hasNearbyOrigin' => false,
])

@php
    $styleUrl = $styleUrl ?: config('living_map.style_url');
    $attribution = $attribution ?: config('living_map.attribution');
    $eventsUrl = $eventsUrl ?: route('events.index');
    $compact = in_array($mode, ['home', 'public'], true);
    $latAttr = $latitude !== null && $latitude !== '' ? (float) $latitude : null;
    $lngAttr = $longitude !== null && $longitude !== '' ? (float) $longitude : null;
    $kmAttr = $distanceKm !== null && $distanceKm !== '' ? (int) $distanceKm : (int) config('location.default_distance_km', 25);
    $modeAttr = is_string($discoveryMode) && $discoveryMode !== '' ? $discoveryMode : 'nationwide';
@endphp

@once
    @push('head')
        @if (($mode ?? 'preview') === 'preview')
            <meta name="robots" content="noindex,nofollow">
        @endif
        <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
        <style>
            .lm-shell { --lm-purple:#7138F5; --lm-orange:#FF762E; --lm-ink:#2A2150; overflow-x: clip; }
            .lm-shell [dir="auto"] { unicode-bidi: isolate; }
            .lm-banner {
                border-radius: 1rem; border: 1px solid #fcd34d;
                background: linear-gradient(90deg,#fffbeb,#fef3c7);
                padding: .85rem 1rem; color: #92400e; font-size: .8125rem; font-weight: 600;
            }
            .lm-split {
                display: grid; gap: 1rem; align-items: stretch;
            }
            @media (min-width: 1024px) {
                .lm-split { grid-template-columns: minmax(0,1fr) minmax(16rem, 32%); }
                .lm-split--compact { grid-template-columns: minmax(0,1fr) minmax(14rem, 30%); }
            }
            .lm-stage {
                position: relative; overflow: hidden; border-radius: 1.35rem;
                border: 1px solid rgba(232,234,242,.9);
                background: linear-gradient(160deg,#DCECFF 0%, #F8F1FF 55%, #FFE9D6 100%);
                box-shadow: 0 24px 50px -28px rgba(49,24,110,.5);
                min-height: 280px; height: min(58vh, 520px);
            }
            .lm-stage--compact { height: min(42vh, 340px); min-height: 240px; }
            .lm-map { position:absolute; inset:0; }
            .lm-map .maplibregl-canvas { outline: none; }
            .lm-atmosphere {
                pointer-events:none; position:absolute; inset:0; z-index:2;
                background:
                    radial-gradient(120% 80% at 70% 10%, rgba(255,255,255,.32), transparent 55%),
                    linear-gradient(to top, rgba(42,33,80,.14), transparent 28%);
            }
            .lm-hud {
                position:absolute; z-index:5; display:flex; flex-direction:column; gap:.4rem;
                inset-inline-end: .75rem; top: .75rem;
            }
            .lm-hud button {
                width:2.4rem; height:2.4rem; border-radius:.9rem; border:1px solid rgba(255,255,255,.7);
                background:rgba(255,255,255,.92); color:#19213D; font-weight:800; cursor:pointer;
                box-shadow:0 10px 22px -14px rgba(15,23,42,.5); backdrop-filter: blur(8px);
            }
            .lm-hud button[aria-pressed="true"] { background: var(--lm-purple); color:#fff; border-color:transparent; }
            .lm-loc {
                position:absolute; z-index:5; inset-inline-start:.75rem; top:.75rem; max-width:15rem;
                border-radius:1rem; border:1px solid rgba(255,255,255,.75);
                background:rgba(255,255,255,.9); backdrop-filter: blur(10px);
                padding:.7rem .8rem; box-shadow:0 14px 28px -18px rgba(42,33,80,.45);
            }
            .lm-loc p { margin:0; font-size:.65rem; font-weight:700; color:#7C7399; text-transform:uppercase; }
            .lm-loc strong { display:block; margin-top:.15rem; font-size:.88rem; color:var(--lm-ink); }
            .lm-marker {
                width:48px; height:60px; cursor:pointer; border:0; background:transparent; padding:0;
                filter: drop-shadow(0 10px 14px rgba(49,24,110,.28));
                transition: transform .28s cubic-bezier(.2,.8,.2,1);
            }
            .lm-marker.is-selected { transform: translateY(-8px) scale(1.12); }
            .lm-marker.is-float { animation: lmFloat 2.8s ease-in-out infinite; }
            .lm-marker svg { display:block; width:48px; height:60px; }
            .lm-collection {
                display:flex; flex-direction:column; align-items:flex-start; gap:.1rem;
                min-width:6.8rem; max-width:9rem; border:0; cursor:pointer; text-align:start;
                border-radius:.95rem; padding:.4rem .55rem .45rem;
                background: rgba(255,255,255,.95); color: var(--lm-ink);
                box-shadow: 0 12px 24px -14px rgba(49,24,110,.42), 0 0 0 1px rgba(113,56,245,.16);
            }
            .lm-collection--cluster { background: linear-gradient(145deg,#fff, #eef2ff); }
            .lm-collection__name {
                font:800 .7rem/1.15 ui-sans-serif,system-ui,sans-serif;
                max-width:8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            }
            .lm-collection__meta { font:600 .6rem/1.1 ui-sans-serif,system-ui,sans-serif; color:#6B6280; }
            .lm-collection__meta strong { color: var(--lm-purple); font-weight:800; }
            .lm-collection__cta {
                margin-top:.12rem; font:800 .55rem/1 ui-sans-serif,system-ui,sans-serif;
                color:#fff; background:linear-gradient(135deg,#8B5CF6,#7138F5);
                border-radius:.5rem; padding:.2rem .42rem;
            }
            .lm-collection.is-selected { transform: translateY(-2px) scale(1.03); box-shadow: 0 14px 26px -12px rgba(113,56,245,.5), 0 0 0 2px rgba(255,118,46,.3); }
            @keyframes lmFloat { 0%,100%{ transform: translateY(0);} 50%{ transform: translateY(-7px);} }
            @media (prefers-reduced-motion: reduce) {
                .lm-marker.is-float { animation: none; }
            }
            .lm-card {
                position:absolute; z-index:6; width:min(17.5rem, calc(100% - 1.25rem));
                border-radius:1.15rem; overflow:hidden;
                background: rgba(255,255,255,.95); backdrop-filter: blur(12px);
                border:1px solid rgba(255,255,255,.8);
                box-shadow:0 24px 44px -24px rgba(42,33,80,.55);
                transform: translateY(8px) scale(.98); opacity:0;
                transition: opacity .28s ease, transform .28s ease;
                pointer-events:none;
            }
            .lm-card.is-open { opacity:1; transform:none; pointer-events:auto; }
            .lm-card img { width:100%; height:7.25rem; object-fit:cover; display:block; }
            .lm-card__fallback {
                height:7.25rem; display:flex; align-items:center; justify-content:center; font-size:2.4rem;
                background: linear-gradient(135deg, var(--s), #fff);
            }
            .lm-card__body { padding:.75rem .85rem .9rem; }
            .lm-card__cat {
                display:inline-flex; border-radius:9999px; padding:.15rem .55rem;
                font-size:.62rem; font-weight:800;
            }
            .lm-card h3 { margin:.4rem 0 .25rem; font-size:.95rem; font-weight:800; color:var(--lm-ink); }
            .lm-card__meta { margin:0; font-size:.68rem; color:#6B6280; font-weight:600; }
            .lm-card__cta {
                display:inline-flex; margin-top:.65rem; border-radius:.8rem;
                background:linear-gradient(135deg,#8B5CF6,#7138F5); color:#fff;
                font-size:.72rem; font-weight:800; padding:.45rem .75rem; text-decoration:none;
            }
            .lm-city-panel {
                position:absolute; z-index:7; inset-inline-start:.75rem; bottom:.75rem;
                width:min(18rem, calc(100% - 5rem)); max-height:42%;
                display:flex; flex-direction:column; border-radius:1.05rem; overflow:hidden;
                background:rgba(255,255,255,.96); backdrop-filter:blur(12px);
                border:1px solid rgba(255,255,255,.85);
                box-shadow:0 20px 36px -22px rgba(42,33,80,.5);
            }
            .lm-city-panel__head {
                display:flex; justify-content:space-between; gap:.5rem;
                padding:.65rem .75rem; border-bottom:1px solid #EEF0F7;
            }
            .lm-city-panel__head p { margin:0; font-size:.6rem; font-weight:800; color:#7C7399; text-transform:uppercase; }
            .lm-city-panel__head strong { display:block; font-size:.88rem; color:var(--lm-ink); }
            .lm-city-panel__head span { display:block; margin-top:.1rem; font-size:.62rem; color:#6B6280; font-weight:600; }
            .lm-city-panel__head button {
                border:0; background:#F1EBFF; color:#7138F5; width:1.7rem; height:1.7rem;
                border-radius:.6rem; font-weight:800; cursor:pointer;
            }
            .lm-city-panel__list { overflow:auto; padding:.3rem; }
            .lm-city-panel__row {
                width:100%; display:flex; gap:.5rem; align-items:center; text-align:start;
                border:0; background:transparent; border-radius:.8rem; padding:.4rem .45rem; cursor:pointer;
            }
            .lm-city-panel__row:hover, .lm-city-panel__row.is-active { background:#F6F3FF; }
            .lm-city-panel__thumb {
                width:2.4rem; height:2.4rem; border-radius:.7rem; overflow:hidden; flex-shrink:0;
                background:#F1EBFF; display:grid; place-items:center;
            }
            .lm-city-panel__thumb img { width:100%; height:100%; object-fit:cover; }
            .lm-city-panel__body { min-width:0; }
            .lm-city-panel__body strong {
                display:block; font-size:.75rem; color:var(--lm-ink);
                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
            }
            .lm-city-panel__body em {
                display:block; margin-top:.08rem; font-style:normal; font-size:.6rem; color:#6B6280; font-weight:600;
            }
            .lm-city-panel__empty { margin:0; padding:.55rem; font-size:.72rem; color:#6B6280; }
            .lm-side {
                display:flex; flex-direction:column; min-height:0;
                border-radius:1.35rem; border:1px solid #E8EAF2; background:#fff;
                box-shadow: 0 18px 36px -28px rgba(42,33,80,.4); overflow:hidden;
            }
            .lm-side__head {
                display:flex; align-items:center; justify-content:space-between; gap:.5rem;
                padding:.85rem 1rem; border-bottom:1px solid #EEF0F7;
            }
            .lm-side__head h2 { margin:0; font-size:.95rem; font-weight:800; color:#19213D; }
            .lm-list {
                flex:1; overflow:auto; padding:.55rem; display:flex; flex-direction:column; gap:.45rem;
                max-height: min(58vh, 520px);
            }
            .lm-stage--compact ~ .lm-side .lm-list,
            .lm-split--compact .lm-list { max-height: min(42vh, 340px); }
            .lm-list__card {
                display:flex; gap:.7rem; width:100%; text-align:start; cursor:pointer;
                border:1px solid #E8EAF2; border-radius:1rem; background:#fff; padding:.45rem;
                transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
            }
            .lm-list__card:hover, .lm-list__card.is-active {
                border-color:#C4B5FD; box-shadow:0 14px 28px -20px rgba(113,56,245,.45);
                transform: translateY(-1px);
            }
            .lm-list__media {
                width:4.75rem; height:4.75rem; border-radius:.8rem; overflow:hidden; flex-shrink:0;
                background:linear-gradient(135deg,#F1EBFF,#fff); display:grid; place-items:center;
            }
            .lm-list__img { width:100%; height:100%; object-fit:cover; display:block; }
            .lm-list__fallback { font-size:1.5rem; }
            .lm-list__body { min-width:0; padding-block:.15rem; }
            .lm-list__cat { display:block; font-size:.62rem; font-weight:800; }
            .lm-list__body strong {
                display:block; margin-top:.12rem; font-size:.82rem; color:var(--lm-ink);
                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
            }
            .lm-list__body em {
                display:block; margin-top:.2rem; font-style:normal; font-size:.62rem; color:#6B6280; font-weight:600;
            }
            .lm-list__empty { margin:0; padding:1rem; font-size:.78rem; color:#6B6280; font-weight:600; }
            .lm-status { font-size:.7rem; font-weight:700; color:#6B6280; }
            @media (max-width: 1023px) {
                .lm-stage, .lm-stage--compact { height: 260px; min-height: 240px; }
                .lm-list { max-height: 22rem; }
            }
        </style>
    @endpush
@endonce

<div {{ $attributes->class(['lm-shell']) }}
     data-living-map
     data-mode="{{ $mode }}"
     data-style-url="{{ $styleUrl }}"
     data-attribution="{{ $attribution }}"
     data-locale="{{ app()->getLocale() }}"
     data-events-url="{{ $eventsUrl }}"
     @if ($latAttr !== null) data-lat="{{ $latAttr }}" @endif
     @if ($lngAttr !== null) data-lng="{{ $lngAttr }}" @endif
     data-distance-km="{{ $kmAttr }}"
     data-discovery-mode="{{ $modeAttr }}"
     @if ($locationLabel) data-location-label="{{ $locationLabel }}" @endif
     data-i18n-featured-region="{{ __('living_map.featured_region') }}"
     data-i18n-featured-region-sub="{{ __('living_map.featured_region_sub') }}"
     data-i18n-composing="{{ __('living_map.composing') }}"
     data-i18n-loading-rtl="{{ __('living_map.loading_rtl') }}"
     data-i18n-loading-style="{{ __('living_map.loading_style') }}"
     data-i18n-ready="{{ __('living_map.ready') }}"
     data-i18n-ready-event-tour="{{ __('living_map.ready_event_tour') }}"
     data-i18n-ready-discovery-tour="{{ __('living_map.ready_discovery_tour') }}"
     data-i18n-open="{{ __('living_map.open') }}"
     data-i18n-events-word="{{ app()->getLocale() === 'ar' ? 'فعاليات' : 'events' }}"
     data-i18n-view-event="{{ __('living_map.view_event') }}"
     data-i18n-approximate-city-location="{{ __('living_map.approximate_city_location') }}"
     data-i18n-approximate-city-locations="{{ __('living_map.approximate_city_locations') }}"
     data-i18n-exact-venue="{{ __('living_map.exact_venue') }}"
     data-i18n-city-level-location="{{ __('living_map.city_level_location') }}"
     data-i18n-living-city-sub="{{ __('living_map.living_city_sub') }}"
     data-i18n-loading-city-events="{{ __('living_map.loading_city_events') }}"
     data-i18n-city-events-failed="{{ __('living_map.city_events_failed') }}"
     data-i18n-discovery-unavailable="{{ __('living_map.discovery_unavailable') }}"
     data-i18n-maplibre-failed="{{ __('living_map.maplibre_failed') }}"
     data-i18n-style-fallback="{{ __('living_map.style_fallback') }}"
     data-i18n-reduced-motion-tour="{{ __('living_map.reduced_motion_tour') }}"
     data-i18n-not-enough-for-tour="{{ __('living_map.not_enough_for_tour') }}"
     data-i18n-no-eligible-events="{{ __('living_map.no_eligible_events') }}"
     data-i18n-events-in-label="{{ __('living_map.events_in_label') }}"
     data-i18n-events-in-city="{{ __('living_map.events_in_city') }}"
     data-i18n-list-heading="{{ __('living_map.list_heading') }}"
     data-i18n-list-empty="{{ __('living_map.list_empty') }}"
     data-i18n-list-loading="{{ __('living_map.list_loading') }}"
     data-i18n-going="{{ __('living_map.going') }}"
     data-i18n-city-cluster="{{ __('living_map.city_cluster') }}"
     data-i18n-zoom-expand-cluster="{{ __('living_map.zoom_to_expand_cluster') }}"
     data-i18n-ambient-showcase="{{ __('living_map.ambient_showcase') }}"
     data-i18n-close-city="{{ __('living_map.close_city_collection') }}">

    @if ($showBanner)
        <div class="lm-banner mb-4" role="status">{{ __('living_map.banner_preview') }}</div>
    @endif

    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            @if ($compact)
                <h2 class="text-[1.15rem] font-extrabold tracking-tight text-[#19213D]">{{ __('dashboard.explore_around_you') }}</h2>
                <p class="mt-0.5 text-[12px] text-slate-500">{{ __('dashboard.explore_around_you_sub') }}</p>
            @else
                <h1 class="text-[clamp(1.35rem,2.6vw,1.85rem)] font-extrabold tracking-tight text-[#19213D]">
                    {{ __('living_map.title') }}
                    <span class="text-[#FF762E]">· {{ __('living_map.title_living') }}</span>
                </h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('living_map.subtitle') }}</p>
            @endif
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if ($compact)
                <a href="{{ route('events.index', ['view' => 'map']) }}"
                   class="inline-flex items-center rounded-xl bg-[#7138F5] px-3.5 py-2 text-xs font-bold text-white no-underline shadow-sm hover:bg-[#5825D6]">
                    {{ __('dashboard.explore_full_map') }}
                </a>
            @endif
            <p class="lm-status" data-lm-status>{{ __('living_map.composing') }}</p>
        </div>
    </div>

    <div @class(['lm-split', 'lm-split--compact' => $compact])>
        <div @class(['lm-stage', 'lm-stage--compact' => $compact]) data-lm-stage>
            <div class="lm-map" data-lm-map role="application" aria-label="{{ __('living_map.aria_map') }}"></div>
            <div class="lm-atmosphere" aria-hidden="true"></div>

            <div class="lm-loc">
                <p>{{ __('living_map.focus') }}</p>
                <strong data-lm-focus-label dir="auto">{{ __('living_map.featured_region') }}</strong>
                <p class="mt-1 normal-case tracking-normal" data-lm-focus-sub dir="auto">{{ __('living_map.composing') }}</p>
            </div>

            @if ($showTourControls)
                <div class="lm-hud" aria-label="{{ __('living_map.map_controls') }}">
                    <button type="button" data-lm-tour title="{{ __('living_map.tour_play') }}" aria-pressed="false">▶</button>
                    <button type="button" data-lm-prev title="{{ __('living_map.previous_event') }}">‹</button>
                    <button type="button" data-lm-next title="{{ __('living_map.next_event') }}">›</button>
                    <button type="button" data-lm-zoom-in title="{{ __('living_map.zoom_in') }}">+</button>
                    <button type="button" data-lm-zoom-out title="{{ __('living_map.zoom_out') }}">−</button>
                    <button type="button" data-lm-reset title="{{ __('living_map.reset_framing') }}">↻</button>
                </div>
            @endif

            <aside class="lm-city-panel" data-lm-city-panel hidden aria-live="polite"></aside>

            @unless ($compact)
                <article class="lm-card" data-lm-card hidden>
                    <div data-lm-card-media></div>
                    <div class="lm-card__body">
                        <span class="lm-card__cat" data-lm-card-cat></span>
                        <h3 data-lm-card-title dir="auto"></h3>
                        <p class="lm-card__meta" data-lm-card-meta dir="auto"></p>
                        <a class="lm-card__cta" data-lm-card-link href="#">{{ __('living_map.view_event') }}</a>
                    </div>
                </article>
            @endunless
        </div>

        <aside class="lm-side" aria-label="{{ __('living_map.list_heading') }}">
            <div class="lm-side__head">
                <h2 data-lm-list-heading dir="auto">{{ __('living_map.list_heading') }}</h2>
                @unless ($compact)
                    <a href="{{ route('events.index', ['view' => 'map']) }}" class="text-[11px] font-bold text-[#7138F5] no-underline">
                        {{ __('living_map.open_production_discover') }}
                    </a>
                @endunless
            </div>
            <div class="lm-list fancy-scroll" data-lm-list>
                <p class="lm-list__empty">{{ __('living_map.list_loading') }}</p>
            </div>
        </aside>
    </div>
</div>

@once
    @push('scripts')
        <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js" crossorigin></script>
        <script>
            (function () {
                try {
                    if (window.maplibregl && typeof window.maplibregl.setRTLTextPlugin === 'function') {
                        window.maplibregl.setRTLTextPlugin(
                            'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.2.3/mapbox-gl-rtl-text.js',
                            false
                        );
                    }
                } catch (e) {}
            })();
        </script>
        @vite(['resources/js/living-map-v3.js'])
    @endpush
@endonce
