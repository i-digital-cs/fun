@props([
    'mode' => 'preview', // home|discover|public|preview
    'variant' => null, // null|v34|v35 — v35 is isolated premium preview skin
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
    'cityId' => null,
    'showRadiusControl' => null,
    'showList' => null,
    'showHeading' => null,
])

@php
    $styleUrl = $styleUrl ?: config('living_map.style_url');
    $attribution = $attribution ?: config('living_map.attribution');
    $eventsUrl = $eventsUrl ?: route('events.index');
    $variantAttr = is_string($variant) && $variant !== '' ? strtolower($variant) : null;
    $isV35 = $variantAttr === 'v35';
    // Embedded = Home/Dashboard chrome only. Visual map chrome matches preview (no feature fork).
    $embedded = in_array($mode, ['home', 'public'], true);
    $isDiscover = $mode === 'discover';
    $latAttr = $latitude !== null && $latitude !== '' ? (float) $latitude : null;
    $lngAttr = $longitude !== null && $longitude !== '' ? (float) $longitude : null;
    $kmAttr = $distanceKm !== null && $distanceKm !== '' ? (int) $distanceKm : (int) config('location.default_distance_km', 25);
    $modeAttr = is_string($discoveryMode) && $discoveryMode !== '' ? $discoveryMode : 'nationwide';
    $cityIdAttr = $cityId !== null && $cityId !== '' ? (int) $cityId : null;
    $showRadius = $showRadiusControl === null ? ($embedded || $isV35) : (bool) $showRadiusControl;
    $showSideList = $showList === null ? ! $isDiscover : (bool) $showList;
    $showHead = $showHeading === null ? ! $isDiscover : (bool) $showHeading;
    $radiusOptions = array_values(array_map('intval', config('location.distances_km', [5, 10, 25, 50, 100, 0])));
    $radiusSupported = $modeAttr !== 'nationwide';
    $reverseUrl = url('/api/places/reverse');
@endphp

@once
    @push('head')
        @if (($mode ?? 'preview') === 'preview')
            <meta name="robots" content="noindex,nofollow">
        @endif
        <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
        @if ($isV35)
            @vite(['resources/css/living-map-v35.css'])
        @endif
        <style>
            .lm-shell {
                --lm-purple: #7138F5;
                --lm-orange: #FF762E;
                --lm-ink: #2A2150;
                --lm-soft: #F4F0FF;
                --lm-sky: #6EB6FF;
                overflow-x: clip;
            }
            .lm-shell [dir="auto"] { unicode-bidi: isolate; }
            .lm-shell--discover { height: 100%; min-height: 20rem; display: flex; flex-direction: column; }
            .lm-shell--discover .lm-stage-stack { flex: 1; min-height: 0; }
            .lm-shell--discover .lm-stage {
                height: 100%; min-height: 20rem; border-radius: 0.9rem; box-shadow: none;
            }
            .lm-shell--map-only .lm-carousel-wrap { display: none; }
            .lm-shell.is-view-list .lm-stage-stack { display: none; }
            .lm-shell.is-view-list .lm-carousel-wrap { margin-top: 0; }
            .lm-shell.is-view-list .lm-rail {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr));
                gap: .85rem;
                overflow: visible;
                padding-inline: 0;
                scroll-snap-type: none;
            }
            .lm-shell.is-view-list .lm-rail__card { width: auto; min-width: 0; scroll-snap-align: unset; }
            .lm-shell.is-view-list .lm-carousel__nav { display: none; }

            .lm-banner {
                border-radius: 1rem; border: 1px solid #fcd34d;
                background: linear-gradient(90deg,#fffbeb,#fef3c7);
                padding: .85rem 1rem; color: #92400e; font-size: .8125rem; font-weight: 600;
            }

            .lm-head {
                display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;
                gap: .85rem 1rem; margin-bottom: .95rem;
            }
            .lm-head__title {
                margin: 0; font-size: clamp(1.2rem, 2.4vw, 1.65rem); font-weight: 800;
                letter-spacing: -0.02em; color: #19213D;
            }
            .lm-head__title em {
                font-style: normal; color: var(--lm-purple);
            }
            .lm-head__sub { margin: .25rem 0 0; font-size: .78rem; color: #64748b; font-weight: 600; max-width: 36rem; }
            .lm-head__actions { display: flex; flex-wrap: wrap; align-items: center; gap: .55rem; }

            .lm-modes {
                display: inline-flex; align-items: center; gap: .2rem;
                padding: .28rem; border-radius: 9999px;
                background: rgba(255,255,255,.92);
                border: 1px solid rgba(226,232,240,.95);
                box-shadow: 0 10px 24px -18px rgba(42,33,80,.45);
                backdrop-filter: blur(10px);
            }
            .lm-modes button {
                border: 0; background: transparent; cursor: pointer;
                border-radius: 9999px; padding: .45rem .95rem;
                font-size: .72rem; font-weight: 800; color: #64748b;
                transition: background .2s ease, color .2s ease, box-shadow .2s ease;
            }
            .lm-modes button[aria-pressed="true"] {
                background: var(--lm-purple); color: #fff;
                box-shadow: 0 8px 16px -10px rgba(113,56,245,.7);
            }
            .lm-modes button:focus-visible {
                outline: 2px solid var(--lm-purple); outline-offset: 2px;
            }

            .lm-status { font-size: .7rem; font-weight: 700; color: #6B6280; }

            .lm-stage-stack { position: relative; min-width: 0; }
            .lm-stage {
                position: relative; overflow: hidden; border-radius: 1.5rem;
                border: 1px solid rgba(232,234,242,.9);
                background: linear-gradient(160deg, #DCECFF 0%, #F4F0FF 48%, #FFE9D6 100%);
                box-shadow:
                    0 28px 56px -30px rgba(49,24,110,.48),
                    0 0 0 1px rgba(255,255,255,.55) inset;
                min-height: 300px; height: min(62vh, 560px); min-width: 0;
            }
            .lm-map { position: absolute; inset: 0; }
            .lm-map .maplibregl-canvas { outline: none; }
            .lm-map .maplibregl-ctrl-bottom-right { bottom: .55rem; inset-inline-end: .55rem; }
            .lm-map .maplibregl-ctrl-attrib { font-size: 9px; opacity: .75; }
            .lm-atmosphere {
                pointer-events: none; position: absolute; inset: 0; z-index: 2;
                background:
                    radial-gradient(110% 70% at 72% 8%, rgba(255,255,255,.38), transparent 55%),
                    radial-gradient(80% 55% at 12% 88%, rgba(113,56,245,.08), transparent 50%),
                    linear-gradient(to top, rgba(42,33,80,.12), transparent 30%);
            }

            .lm-loc-panel {
                position: absolute; z-index: 5; inset-inline-start: .75rem; top: .75rem;
                width: min(17.5rem, calc(100% - 4.75rem));
                border-radius: 1.15rem;
                border: 1px solid rgba(255,255,255,.78);
                background: rgba(255,255,255,.88);
                backdrop-filter: blur(14px);
                box-shadow: 0 16px 34px -20px rgba(42,33,80,.5);
                padding: .75rem .8rem .85rem;
            }
            .lm-loc-panel[hidden] { display: none !important; }
            .lm-loc-panel__label {
                margin: 0; font-size: .6rem; font-weight: 800; letter-spacing: .04em;
                text-transform: uppercase; color: #7C7399;
            }
            .lm-loc-panel__city {
                display: block; margin-top: .2rem; font-size: .95rem; font-weight: 800;
                color: var(--lm-ink); line-height: 1.2;
            }
            .lm-loc-panel__sub {
                margin: .2rem 0 0; font-size: .65rem; font-weight: 600; color: #6B6280;
            }
            .lm-loc-panel__gps {
                margin-top: .65rem; width: 100%; display: inline-flex; align-items: center;
                justify-content: center; gap: .35rem; border: 0; cursor: pointer;
                border-radius: .85rem; padding: .55rem .7rem;
                background: linear-gradient(135deg, #8B5CF6, #7138F5);
                color: #fff; font-size: .72rem; font-weight: 800;
                box-shadow: 0 10px 20px -12px rgba(113,56,245,.75);
            }
            .lm-loc-panel__gps:disabled { opacity: .65; cursor: wait; }
            .lm-loc-panel__gps:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
            .lm-dist {
                display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .7rem;
            }
            .lm-dist button {
                border: 1px solid #E8EAF2; background: #fff; color: #475569;
                border-radius: 9999px; padding: .28rem .55rem; cursor: pointer;
                font-size: .62rem; font-weight: 800;
                transition: background .15s ease, color .15s ease, border-color .15s ease;
            }
            .lm-dist button[aria-pressed="true"] {
                background: var(--lm-purple); color: #fff; border-color: transparent;
            }
            .lm-dist button:disabled { opacity: .45; cursor: not-allowed; }
            .lm-dist button:focus-visible { outline: 2px solid var(--lm-purple); outline-offset: 1px; }

            .lm-hud {
                position: absolute; z-index: 5; display: flex; flex-direction: column; gap: .4rem;
                inset-inline-end: .75rem; top: .75rem;
            }
            .lm-hud button {
                width: 2.45rem; height: 2.45rem; border-radius: .95rem;
                border: 1px solid rgba(255,255,255,.75);
                background: rgba(255,255,255,.92); color: #19213D; font-weight: 800; cursor: pointer;
                box-shadow: 0 10px 22px -14px rgba(15,23,42,.5); backdrop-filter: blur(8px);
                font-size: .95rem; line-height: 1;
            }
            .lm-hud button[aria-pressed="true"] { background: var(--lm-purple); color: #fff; border-color: transparent; }
            .lm-hud button:focus-visible { outline: 2px solid var(--lm-purple); outline-offset: 2px; }

            /*
             * MapLibre sets `transform` on the marker root for geographic placement.
             * Never animate/override transform on .lm-marker — only on .lm-marker__visual.
             */
            .lm-marker {
                position: relative;
                width: 48px; height: 60px; cursor: pointer; border: 0; background: transparent; padding: 0;
            }
            .lm-marker__visual {
                position: absolute; inset: 0; display: block;
                transform-origin: 50% 100%;
                filter: drop-shadow(0 10px 14px rgba(49,24,110,.28));
                transition: transform .28s cubic-bezier(.2,.8,.2,1);
            }
            .lm-marker.is-selected .lm-marker__visual { transform: translateY(-8px) scale(1.12); }
            .lm-marker.is-float .lm-marker__visual { animation: lmFloat 2.8s ease-in-out infinite; }
            .lm-marker.is-pulse .lm-marker__visual::after,
            .lm-marker.is-pulse::after {
                content: ''; position: absolute; left: 50%; top: 42%;
                width: 18px; height: 18px; margin: -9px 0 0 -9px; border-radius: 9999px;
                background: currentColor; opacity: .35; animation: lmPulse 1.8s ease-out infinite;
                pointer-events: none;
            }
            .lm-marker svg { display: block; width: 48px; height: 60px; }
            .lm-collection {
                display: flex; flex-direction: column; align-items: flex-start; gap: .1rem;
                min-width: 6.8rem; max-width: 9rem; border: 0; cursor: pointer; text-align: start;
                border-radius: .95rem; padding: .4rem .55rem .45rem;
                background: rgba(255,255,255,.95); color: var(--lm-ink);
                box-shadow: 0 12px 24px -14px rgba(49,24,110,.42), 0 0 0 1px rgba(113,56,245,.16);
            }
            .lm-collection--cluster { background: linear-gradient(145deg,#fff, #eef2ff); }
            .lm-collection__name {
                font: 800 .7rem/1.15 ui-sans-serif,system-ui,sans-serif;
                max-width: 8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            }
            .lm-collection__meta { font: 600 .6rem/1.1 ui-sans-serif,system-ui,sans-serif; color: #6B6280; }
            .lm-collection__meta strong { color: var(--lm-purple); font-weight: 800; }
            .lm-collection__cta {
                margin-top: .12rem; font: 800 .55rem/1 ui-sans-serif,system-ui,sans-serif;
                color: #fff; background: linear-gradient(135deg,#8B5CF6,#7138F5);
                border-radius: .5rem; padding: .2rem .42rem;
            }
            .lm-collection.is-selected {
                transform: translateY(-2px) scale(1.03);
                box-shadow: 0 14px 26px -12px rgba(113,56,245,.5), 0 0 0 2px rgba(255,118,46,.3);
            }
            .lm-cluster-pin {
                width: 2.55rem; height: 2.55rem; border-radius: 9999px; border: 2.5px solid #fff;
                background: var(--lm-cluster, #3B82F6); color: #fff; cursor: pointer;
                display: grid; place-items: center; font: 800 .85rem/1 ui-sans-serif,system-ui,sans-serif;
                box-shadow:
                    0 0 0 6px rgba(59,130,246,.22),
                    0 12px 22px -10px rgba(15,23,42,.45);
                transition: transform .22s ease;
            }
            .lm-cluster-pin.is-selected, .lm-cluster-pin.is-pulse {
                transform: scale(1.08);
            }
            @keyframes lmFloat { 0%,100%{ transform: translateY(0);} 50%{ transform: translateY(-7px);} }
            @keyframes lmPulse {
                0% { transform: scale(.6); opacity: .45; }
                100% { transform: scale(2.4); opacity: 0; }
            }
            @media (prefers-reduced-motion: reduce) {
                .lm-marker.is-float .lm-marker__visual { animation: none; }
                .lm-marker.is-pulse::after,
                .lm-marker.is-pulse .lm-marker__visual::after { animation: none; display: none; }
            }

            .lm-card {
                position: absolute; z-index: 6; width: min(17.5rem, calc(100% - 1.25rem));
                border-radius: 1.2rem; overflow: hidden;
                background: rgba(255,255,255,.94); backdrop-filter: blur(14px);
                border: 1px solid rgba(255,255,255,.85);
                box-shadow: 0 24px 44px -24px rgba(42,33,80,.55);
                transform: translateY(8px) scale(.98); opacity: 0;
                transition: opacity .28s ease, transform .28s ease;
                pointer-events: none;
            }
            .lm-card.is-open { opacity: 1; transform: none; pointer-events: auto; }
            .lm-card img { width: 100%; height: 7.25rem; object-fit: cover; display: block; }
            .lm-card__fallback {
                height: 7.25rem; display: flex; align-items: center; justify-content: center; font-size: 2.4rem;
                background: linear-gradient(135deg, var(--s), #fff);
            }
            .lm-card__body { padding: .75rem .85rem .9rem; }
            .lm-card__cat {
                display: inline-flex; border-radius: 9999px; padding: .15rem .55rem;
                font-size: .62rem; font-weight: 800;
            }
            .lm-card h3 { margin: .4rem 0 .25rem; font-size: .95rem; font-weight: 800; color: var(--lm-ink); }
            .lm-card__meta { margin: 0; font-size: .68rem; color: #6B6280; font-weight: 600; }
            .lm-card__cta {
                display: inline-flex; margin-top: .65rem; border-radius: .8rem;
                background: linear-gradient(135deg,#8B5CF6,#7138F5); color: #fff;
                font-size: .72rem; font-weight: 800; padding: .45rem .75rem; text-decoration: none;
            }

            .lm-city-panel {
                position: absolute; z-index: 7; inset-inline-start: .75rem; bottom: .75rem;
                width: min(18rem, calc(100% - 5rem)); max-height: 42%;
                display: flex; flex-direction: column; border-radius: 1.05rem; overflow: hidden;
                background: rgba(255,255,255,.96); backdrop-filter: blur(12px);
                border: 1px solid rgba(255,255,255,.85);
                box-shadow: 0 20px 36px -22px rgba(42,33,80,.5);
            }
            .lm-city-panel__head {
                display: flex; justify-content: space-between; gap: .5rem;
                padding: .65rem .75rem; border-bottom: 1px solid #EEF0F7;
            }
            .lm-city-panel__head p { margin: 0; font-size: .6rem; font-weight: 800; color: #7C7399; text-transform: uppercase; }
            .lm-city-panel__head strong { display: block; font-size: .88rem; color: var(--lm-ink); }
            .lm-city-panel__head span { display: block; margin-top: .1rem; font-size: .62rem; color: #6B6280; font-weight: 600; }
            .lm-city-panel__head button {
                border: 0; background: #F1EBFF; color: #7138F5; width: 1.7rem; height: 1.7rem;
                border-radius: .6rem; font-weight: 800; cursor: pointer;
            }
            .lm-city-panel__list { overflow: auto; padding: .3rem; }
            .lm-city-panel__row {
                width: 100%; display: flex; gap: .5rem; align-items: center; text-align: start;
                border: 0; background: transparent; border-radius: .8rem; padding: .4rem .45rem; cursor: pointer;
            }
            .lm-city-panel__row:hover, .lm-city-panel__row.is-active { background: #F6F3FF; }
            .lm-city-panel__thumb {
                width: 2.4rem; height: 2.4rem; border-radius: .7rem; overflow: hidden; flex-shrink: 0;
                background: #F1EBFF; display: grid; place-items: center;
            }
            .lm-city-panel__thumb img { width: 100%; height: 100%; object-fit: cover; }
            .lm-city-panel__body { min-width: 0; }
            .lm-city-panel__body strong {
                display: block; font-size: .75rem; color: var(--lm-ink);
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .lm-city-panel__body em {
                display: block; margin-top: .08rem; font-style: normal; font-size: .6rem; color: #6B6280; font-weight: 600;
            }
            .lm-city-panel__empty { margin: 0; padding: .55rem; font-size: .72rem; color: #6B6280; }

            .lm-carousel-wrap {
                margin-top: 1rem; position: relative; min-width: 0;
            }
            .lm-carousel__head {
                display: flex; align-items: baseline; justify-content: space-between; gap: .75rem;
                margin-bottom: .7rem;
            }
            .lm-carousel__head h2 {
                margin: 0; font-size: 1.05rem; font-weight: 800; color: #19213D; letter-spacing: -0.01em;
            }
            .lm-carousel__head a {
                font-size: .72rem; font-weight: 800; color: var(--lm-purple); text-decoration: none;
                white-space: nowrap;
            }
            .lm-carousel {
                position: relative;
            }
            .lm-rail {
                display: flex; gap: .85rem; overflow-x: auto; overflow-y: hidden;
                scroll-snap-type: x mandatory; scroll-behavior: smooth;
                padding: .15rem .15rem .55rem; -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            .lm-rail:focus-visible { outline: 2px solid var(--lm-purple); outline-offset: 2px; }
            .lm-rail__card {
                flex: 0 0 auto; width: min(15.75rem, 78vw); scroll-snap-align: start;
                display: flex; flex-direction: column; text-align: start; cursor: pointer;
                border: 1px solid #E8EAF2; border-radius: 1.15rem; background: #fff;
                padding: 0; overflow: hidden;
                box-shadow: 0 14px 28px -22px rgba(42,33,80,.4);
                transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
            }
            .lm-rail__card:hover, .lm-rail__card.is-active {
                border-color: #C4B5FD;
                box-shadow: 0 18px 34px -18px rgba(113,56,245,.42);
                transform: translateY(-2px);
            }
            .lm-rail__card.is-active { outline: 2px solid rgba(113,56,245,.35); outline-offset: 0; }
            .lm-rail__media {
                position: relative; height: 8.1rem; background: linear-gradient(135deg, #F1EBFF, #fff);
                overflow: hidden;
            }
            .lm-rail__img { width: 100%; height: 100%; object-fit: cover; display: block; }
            .lm-rail__fallback {
                width: 100%; height: 100%; display: grid; place-items: center; font-size: 2rem;
                background: linear-gradient(135deg, var(--s, #F1EBFF), #fff);
            }
            .lm-rail__badge {
                position: absolute; inset-inline-start: .55rem; top: .55rem;
                display: inline-flex; align-items: center; gap: .2rem;
                border-radius: 9999px; padding: .22rem .5rem;
                font-size: .6rem; font-weight: 800;
                background: rgba(255,255,255,.92); backdrop-filter: blur(6px);
                box-shadow: 0 6px 14px -8px rgba(15,23,42,.35);
            }
            .lm-rail__dist {
                position: absolute; inset-inline-end: .55rem; top: .55rem;
                border-radius: 9999px; padding: .22rem .5rem;
                font-size: .58rem; font-weight: 800; color: #fff;
                background: rgba(42,33,80,.72); backdrop-filter: blur(6px);
            }
            .lm-rail__body { padding: .7rem .75rem .8rem; min-width: 0; }
            .lm-rail__body strong {
                display: block; font-size: .86rem; color: var(--lm-ink); line-height: 1.25;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .lm-rail__meta {
                display: block; margin-top: .35rem; font-size: .65rem; color: #6B6280; font-weight: 600;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .lm-rail__foot {
                display: flex; align-items: center; justify-content: space-between; gap: .5rem;
                margin-top: .55rem;
            }
            .lm-rail__going {
                font-size: .62rem; font-weight: 700; color: #64748b;
            }
            .lm-rail__go {
                width: 1.85rem; height: 1.85rem; border-radius: .65rem; border: 0;
                display: inline-grid; place-items: center; text-decoration: none;
                background: linear-gradient(135deg, #8B5CF6, #7138F5); color: #fff;
                font-size: .85rem; font-weight: 800; flex-shrink: 0;
                box-shadow: 0 8px 16px -10px rgba(113,56,245,.7);
            }
            .lm-rail__empty {
                margin: 0; padding: 1.1rem .25rem; font-size: .8rem; color: #6B6280; font-weight: 600;
            }
            .lm-carousel__nav {
                position: absolute; inset-block: 0; inset-inline-end: -.15rem;
                display: none; align-items: center; pointer-events: none;
            }
            .lm-carousel__nav button {
                pointer-events: auto; width: 2.4rem; height: 2.4rem; border-radius: 9999px;
                border: 1px solid #E8EAF2; background: rgba(255,255,255,.96);
                box-shadow: 0 12px 24px -14px rgba(15,23,42,.4);
                font-size: 1.1rem; font-weight: 800; color: #19213D; cursor: pointer;
            }
            @media (min-width: 768px) {
                .lm-carousel__nav { display: flex; }
            }

            @media (max-width: 1023px) {
                .lm-stage { height: min(52vh, 420px); min-height: 280px; }
                .lm-loc-panel { width: min(15.5rem, calc(100% - 4.4rem)); padding: .65rem .7rem; }
            }
            @media (max-width: 639px) {
                .lm-stage { height: min(48vh, 360px); min-height: 260px; border-radius: 1.25rem; }
                .lm-loc-panel {
                    inset-inline-start: .55rem; top: .55rem;
                    width: min(12.4rem, calc(100% - 3.7rem));
                    padding: .55rem .6rem .6rem;
                }
                .lm-loc-panel__city { font-size: .82rem; }
                .lm-loc-panel__gps { margin-top: .45rem; padding: .42rem .55rem; font-size: .66rem; }
                .lm-dist { margin-top: .45rem; gap: .22rem; }
                .lm-dist button { padding: .2rem .42rem; font-size: .58rem; }
                .lm-hud { inset-inline-end: .55rem; top: .55rem; gap: .3rem; }
                .lm-hud button { width: 2.2rem; height: 2.2rem; border-radius: .8rem; }
                .lm-card {
                    position: absolute; inset-inline: .55rem; bottom: .55rem; top: auto !important; left: .55rem !important;
                    width: auto !important; max-width: none;
                }
                .lm-city-panel {
                    inset-inline-start: .55rem; bottom: .55rem;
                    width: min(100% - 1.1rem, 18rem); max-height: 36%;
                }
                .lm-modes { width: 100%; justify-content: stretch; }
                .lm-modes button { flex: 1; text-align: center; padding-inline: .5rem; }
                .lm-head__title { font-size: 1.2rem; }
                .lm-rail__card { width: min(14.5rem, 82vw); }
            }
        </style>
    @endpush
@endonce

<div {{ $attributes->class([
         'lm-shell',
         'lm-shell--v35' => $isV35,
         'lm-shell--discover' => $isDiscover,
         'lm-shell--map-only' => ! $showSideList,
         'is-view-map' => true,
     ]) }}
     data-living-map
     data-mode="{{ $mode }}"
     @if ($variantAttr) data-variant="{{ $variantAttr }}" @endif
     data-style-url="{{ $styleUrl }}"
     data-attribution="{{ $attribution }}"
     data-locale="{{ app()->getLocale() }}"
     data-events-url="{{ $eventsUrl }}"
     data-reverse-url="{{ $reverseUrl }}"
     @if ($latAttr !== null) data-lat="{{ $latAttr }}" @endif
     @if ($lngAttr !== null) data-lng="{{ $lngAttr }}" @endif
     data-distance-km="{{ $kmAttr }}"
     data-discovery-mode="{{ $modeAttr }}"
     @if ($locationLabel) data-location-label="{{ $locationLabel }}" @endif
     @if ($cityIdAttr !== null) data-city-id="{{ $cityIdAttr }}" @endif
     data-radius-options="{{ e(json_encode($radiusOptions)) }}"
     data-show-radius="{{ $showRadius ? '1' : '0' }}"
     data-authenticated="{{ auth()->check() ? '1' : '0' }}"
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
     data-i18n-exact-event-pin="{{ __('living_map.exact_event_pin') }}"
     data-i18n-city-level-location="{{ __('living_map.city_level_location') }}"
     data-i18n-living-city-sub="{{ __('living_map.living_city_sub') }}"
     data-i18n-events-around="{{ __('living_map.events_around') }}"
     data-i18n-aggregate-events-in-city="{{ __('living_map.aggregate_events_in_city') }}"
     data-i18n-city-cluster-count="{{ __('living_map.city_cluster_count') }}"
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
     data-i18n-list-heading="{{ __('living_map.events_near_you') }}"
     data-i18n-list-empty="{{ __('living_map.list_empty') }}"
     data-i18n-list-loading="{{ __('living_map.list_loading') }}"
     data-i18n-going="{{ __('living_map.going') }}"
     data-i18n-city-cluster="{{ __('living_map.city_cluster') }}"
     data-i18n-zoom-expand-cluster="{{ __('living_map.zoom_to_expand_cluster') }}"
     data-i18n-ambient-showcase="{{ __('living_map.ambient_showcase') }}"
     data-i18n-close-city="{{ __('living_map.close_city_collection') }}"
     data-i18n-radius-label="{{ __('location.discovery_distance') }}"
     data-i18n-all-distances="{{ __('location.all_distances') }}"
     data-i18n-km="{{ __('location.km') }}"
     data-i18n-use-my-location="{{ __('events.use_my_location') }}"
     data-i18n-detecting-location="{{ __('location.detecting_location') }}"
     data-i18n-gps-denied="{{ __('living_map.gps_denied') }}"
     data-i18n-gps-unavailable="{{ __('living_map.gps_unavailable') }}"
     data-i18n-mode-map="{{ __('living_map.mode_map') }}"
     data-i18n-mode-list="{{ __('living_map.mode_list') }}"
     data-i18n-mode-nearby="{{ __('living_map.mode_nearby') }}"
     data-i18n-see-all="{{ __('living_map.see_all_events') }}"
     data-i18n-focus="{{ __('living_map.focus') }}"
     data-i18n-distance-approx="{{ __('living_map.distance_approx') }}"
     data-i18n-live-now="{{ __('living_map.live_now') }}"
     data-i18n-within-km="{{ __('living_map.within_km') }}"
     data-i18n-event-spotlight="{{ __('living_map.event_spotlight') }}"
     data-i18n-dismiss-spotlight="{{ __('living_map.dismiss_spotlight') }}"
     data-i18n-spotlight-approx-note="{{ __('living_map.spotlight_approx_note') }}">

    @if ($showBanner)
        <div class="lm-banner mb-4" role="status">{{ __('living_map.banner_preview') }}</div>
    @endif

    @if ($showHead)
        <div class="lm-head">
            <div class="min-w-0">
                @if ($embedded)
                    <h2 class="lm-head__title">
                        {{ __('dashboard.explore_around_you') }}
                    </h2>
                    <p class="lm-head__sub">{{ __('dashboard.explore_around_you_sub') }}</p>
                @else
                    <h1 class="lm-head__title">
                        {{ __('living_map.title') }}
                        <em>· {{ __('living_map.title_living') }}</em>
                    </h1>
                    <p class="lm-head__sub">{{ __('living_map.subtitle') }}</p>
                @endif
            </div>
            <div class="lm-head__actions">
                @if ($showSideList)
                    <div class="lm-modes" role="tablist" aria-label="{{ __('living_map.view_modes') }}" data-lm-modes>
                        <button type="button" role="tab" data-lm-view="map" aria-pressed="true">{{ __('living_map.mode_map') }}</button>
                        <button type="button" role="tab" data-lm-view="list" aria-pressed="false">{{ __('living_map.mode_list') }}</button>
                        <button type="button" role="tab" data-lm-view="nearby" aria-pressed="false">{{ __('living_map.mode_nearby') }}</button>
                    </div>
                @endif
                <p class="lm-status" data-lm-status>{{ __('living_map.composing') }}</p>
            </div>
        </div>
    @else
        <p class="sr-only" data-lm-status>{{ __('living_map.composing') }}</p>
    @endif

    <div class="lm-stage-stack">
        <div class="lm-stage" data-lm-stage>
            <div class="lm-map" data-lm-map role="application" aria-label="{{ __('living_map.aria_map') }}"></div>
            <div class="lm-atmosphere" aria-hidden="true"></div>

            @if ($showRadius || $embedded || $mode === 'preview')
                <div class="lm-loc-panel {{ $isV35 ? 'lm-loc-panel--v35' : '' }}" data-lm-loc-panel>
                    @if ($isV35)
                        <div class="lm-loc-panel__row">
                            <span class="lm-loc-panel__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none"><path d="M12 22s7-7.2 7-12.2A7 7 0 1 0 5 9.8C5 14.8 12 22 12 22z" fill="#7138F5"/><circle cx="12" cy="9.5" r="2.6" fill="#fff"/></svg>
                            </span>
                            <div class="lm-loc-panel__city-block min-w-0 flex-1">
                                <button type="button"
                                        class="lm-loc-panel__city-btn"
                                        data-lm-city-trigger
                                        @auth data-location-modal-open @endauth
                                        aria-haspopup="dialog">
                                    <strong class="lm-loc-panel__city" data-lm-focus-label dir="auto">{{ $locationLabel ?: __('living_map.featured_region') }}</strong>
                                    <span class="lm-loc-panel__chev" aria-hidden="true">
                                        <svg viewBox="0 0 20 20" width="14" height="14" fill="none"><path d="M5 7.5l5 5 5-5" stroke="#7138F5" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                </button>
                                <p class="lm-loc-panel__sub" data-lm-focus-sub dir="auto">{{ __('living_map.composing') }}</p>
                                <p class="lm-loc-panel__dist" data-lm-distance-readout dir="auto">
                                    @if ((int) $kmAttr > 0)
                                        {{ __('living_map.within_km', ['km' => (int) $kmAttr]) }}
                                    @else
                                        {{ __('location.all_distances') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <button type="button" class="lm-loc-panel__gps" data-lm-use-location>
                            <span class="lm-loc-panel__gps-ico" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"><circle cx="12" cy="12" r="3.2" stroke="#fff" stroke-width="2"/><path d="M12 3v2.2M12 18.8V21M3 12h2.2M18.8 12H21" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="7.5" stroke="#fff" stroke-width="1.6" opacity=".85"/></svg>
                            </span>
                            {{ __('events.use_my_location') }}
                        </button>
                        @if ($showRadius)
                            <div class="lm-slider" data-lm-radius-wrap @if (! $radiusSupported) hidden @endif>
                                <input type="range"
                                       class="lm-slider__input"
                                       data-lm-radius-slider
                                       min="0"
                                       max="{{ max(0, count($radiusOptions) - 1) }}"
                                       step="1"
                                       value="{{ max(0, array_search((int) $kmAttr, $radiusOptions, true)) }}"
                                       aria-label="{{ __('location.discovery_distance') }}"
                                       @disabled(! $radiusSupported)>
                                <div class="lm-slider__labels" aria-hidden="true">
                                    @foreach ($radiusOptions as $distance)
                                        <span>{{ (int) $distance === 0 ? (app()->getLocale() === 'ar' ? 'الكل' : 'All') : $distance }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="lm-dist sr-only" data-lm-chips-mirror>
                                @foreach ($radiusOptions as $distance)
                                    <button type="button" data-lm-radius-chip value="{{ $distance }}" aria-pressed="{{ (int) $kmAttr === (int) $distance ? 'true' : 'false' }}"></button>
                                @endforeach
                            </div>
                            <select class="sr-only" data-lm-radius tabindex="-1" aria-hidden="true" @disabled(! $radiusSupported)>
                                @foreach ($radiusOptions as $distance)
                                    <option value="{{ $distance }}" @selected((int) $kmAttr === (int) $distance)>{{ $distance }}</option>
                                @endforeach
                            </select>
                        @endif
                    @else
                        <p class="lm-loc-panel__label">{{ __('living_map.focus') }}</p>
                        <strong class="lm-loc-panel__city"
                                data-lm-focus-label
                                dir="auto"
                                @auth
                                    role="button"
                                    tabindex="0"
                                    data-location-modal-open
                                    title="{{ __('weather.change_city') }}"
                                    style="cursor:pointer"
                                @endauth>{{ $locationLabel ?: __('living_map.featured_region') }}</strong>
                        <p class="lm-loc-panel__sub" data-lm-focus-sub dir="auto">{{ __('living_map.composing') }}</p>

                        <button type="button"
                                class="lm-loc-panel__gps"
                                data-lm-use-location>
                            {{ __('events.use_my_location') }}
                        </button>

                        @if ($showRadius)
                            <div class="lm-dist" data-lm-radius-wrap @if (! $radiusSupported) hidden @endif role="group" aria-label="{{ __('location.discovery_distance') }}">
                                @foreach ($radiusOptions as $distance)
                                    <button type="button"
                                            data-lm-radius-chip
                                            value="{{ $distance }}"
                                            aria-pressed="{{ (int) $kmAttr === (int) $distance ? 'true' : 'false' }}"
                                            @disabled(! $radiusSupported)>
                                        @if ((int) $distance === 0)
                                            {{ __('location.all_distances') }}
                                        @else
                                            {{ $distance }} {{ __('location.km') }}
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            <select class="sr-only" data-lm-radius tabindex="-1" aria-hidden="true" @disabled(! $radiusSupported)>
                                @foreach ($radiusOptions as $distance)
                                    <option value="{{ $distance }}" @selected((int) $kmAttr === (int) $distance)>{{ $distance }}</option>
                                @endforeach
                            </select>
                        @endif
                    @endif
                </div>
            @else
                <div class="lm-loc-panel" data-lm-loc-panel>
                    <p class="lm-loc-panel__label">{{ __('living_map.focus') }}</p>
                    <strong class="lm-loc-panel__city" data-lm-focus-label dir="auto">{{ __('living_map.featured_region') }}</strong>
                    <p class="lm-loc-panel__sub" data-lm-focus-sub dir="auto">{{ __('living_map.composing') }}</p>
                </div>
            @endif

            @if ($showTourControls)
                <div class="lm-hud" aria-label="{{ __('living_map.map_controls') }}">
                    <button type="button" data-lm-recenter title="{{ __('living_map.recenter') }}" aria-label="{{ __('living_map.recenter') }}">⌖</button>
                    <button type="button" data-lm-zoom-in title="{{ __('living_map.zoom_in') }}">+</button>
                    <button type="button" data-lm-zoom-out title="{{ __('living_map.zoom_out') }}">−</button>
                    <button type="button" data-lm-tour title="{{ __('living_map.tour_play') }}" aria-pressed="false">▶</button>
                    <button type="button" data-lm-reset title="{{ __('living_map.reset_framing') }}">↻</button>
                </div>
            @endif

            <aside class="lm-city-panel" data-lm-city-panel hidden aria-live="polite"></aside>

            @if ($isV35)
                <aside class="lm-spotlight" data-lm-spotlight hidden aria-live="polite">
                    <div class="lm-spotlight__grab" aria-hidden="true"></div>
                    <button type="button"
                            class="lm-spotlight__dismiss"
                            data-lm-spotlight-dismiss
                            aria-label="{{ __('living_map.dismiss_spotlight') }}">✕</button>
                    <div class="lm-spotlight__topline">
                        <p class="lm-spotlight__eyebrow">{{ __('living_map.event_spotlight') }}</p>
                        <span class="lm-spotlight__cat" data-lm-spotlight-cat></span>
                    </div>
                    <div class="lm-spotlight__body">
                        <div class="lm-spotlight__media" data-lm-spotlight-media></div>
                        <div class="lm-spotlight__copy">
                            <strong class="lm-spotlight__title" data-lm-spotlight-title dir="auto"></strong>
                            <span class="lm-spotlight__meta" data-lm-spotlight-meta dir="auto"></span>
                            <span class="lm-spotlight__place" data-lm-spotlight-place dir="auto"></span>
                        </div>
                    </div>
                    <button type="button" class="lm-spotlight__cta" data-lm-spotlight-open>
                        {{ __('living_map.view_event') }}
                    </button>
                </aside>
            @endif

            <article class="lm-card {{ $isV35 ? 'lm-card--v35' : '' }}" data-lm-card hidden>
                @if ($isV35)
                    <div class="lm-card__inner">
                        <div class="lm-card__thumb" data-lm-card-media></div>
                        <div class="lm-card__content">
                            <div class="lm-card__topline">
                                <span class="lm-card__live" data-lm-card-live hidden>{{ __('living_map.live_now') }}</span>
                                <span class="lm-card__cat" data-lm-card-cat></span>
                            </div>
                            <h3 data-lm-card-title dir="auto"></h3>
                            <p class="lm-card__place" data-lm-card-place dir="auto"></p>
                            <div class="lm-card__foot">
                                <div class="lm-card__people" data-lm-card-people></div>
                                <a class="lm-card__go" data-lm-card-link href="#" aria-label="{{ __('living_map.view_event') }}">›</a>
                            </div>
                        </div>
                    </div>
                    <span class="lm-card__pointer" aria-hidden="true"></span>
                @else
                    <div data-lm-card-media></div>
                    <div class="lm-card__body">
                        <span class="lm-card__cat" data-lm-card-cat></span>
                        <h3 data-lm-card-title dir="auto"></h3>
                        <p class="lm-card__meta" data-lm-card-meta dir="auto"></p>
                        <a class="lm-card__cta" data-lm-card-link href="#">{{ __('living_map.view_event') }}</a>
                    </div>
                @endif
            </article>
        </div>
    </div>

    @if ($showSideList)
        <section class="lm-carousel-wrap" data-lm-carousel-wrap aria-label="{{ __('living_map.events_near_you') }}">
            <div class="lm-carousel__head">
                <h2 data-lm-list-heading dir="auto">{{ __('living_map.events_near_you') }}</h2>
                <a href="{{ route('events.index', ['view' => 'map']) }}">
                    {{ $embedded ? __('living_map.see_all_events') : __('living_map.open_production_discover') }}
                </a>
            </div>
            <div class="lm-carousel">
                <div class="lm-rail fancy-scroll" data-lm-list tabindex="0" role="list">
                    <p class="lm-rail__empty">{{ __('living_map.list_loading') }}</p>
                </div>
                <div class="lm-carousel__nav" aria-hidden="true">
                    <button type="button" data-lm-rail-next aria-label="{{ __('living_map.next_event') }}">›</button>
                </div>
            </div>
        </section>
    @endif
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
