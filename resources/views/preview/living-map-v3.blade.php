@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Living Map V3.2 Preview')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
    <style>
        .lm3-shell {
            --lm3-purple:#7138F5; --lm3-orange:#FF762E; --lm3-canvas:#F8F9FE;
            --lm3-sky:#6EB6FF; --lm3-ink:#2A2150;
        }
        .lm3-bidi, [dir="auto"] { unicode-bidi: isolate; }
        .lm3-banner {
            border-radius: 1rem; border: 1px solid #fcd34d; background: linear-gradient(90deg,#fffbeb,#fef3c7);
            padding: .85rem 1rem; color: #92400e; font-size: .8125rem; font-weight: 600;
        }
        .lm3-stage {
            position: relative; overflow: hidden; border-radius: 1.5rem;
            border: 1px solid rgba(232,234,242,.9); background: linear-gradient(160deg,#DCECFF 0%, #F8F1FF 55%, #FFE9D6 100%);
            box-shadow: 0 28px 60px -30px rgba(49,24,110,.55); height: min(68vh, 620px); min-height: 380px;
        }
        .lm3-map { position:absolute; inset:0; }
        .lm3-map .maplibregl-canvas { outline: none; }
        .lm3-atmosphere {
            pointer-events:none; position:absolute; inset:0; z-index:2;
            background:
                radial-gradient(120% 80% at 70% 10%, rgba(255,255,255,.35), transparent 55%),
                radial-gradient(90% 70% at 20% 90%, rgba(113,56,245,.10), transparent 50%),
                linear-gradient(to top, rgba(42,33,80,.18), transparent 28%);
        }
        .lm3-hud {
            position:absolute; z-index:5; display:flex; flex-direction:column; gap:.45rem;
            inset-inline-end: .85rem; top: .85rem;
        }
        .lm3-hud button {
            width:2.55rem; height:2.55rem; border-radius:.95rem; border:1px solid rgba(255,255,255,.7);
            background:rgba(255,255,255,.92); color:#19213D; font-weight:800; font-size:.85rem;
            box-shadow:0 10px 24px -14px rgba(15,23,42,.5); cursor:pointer; backdrop-filter: blur(8px);
        }
        .lm3-hud button[aria-pressed="true"] { background: var(--lm3-purple); color:#fff; border-color:transparent; }
        .lm3-loc {
            position:absolute; z-index:5; inset-inline-start:.85rem; top:.85rem; max-width:17rem;
            border-radius:1.1rem; border:1px solid rgba(255,255,255,.75);
            background:rgba(255,255,255,.9); backdrop-filter: blur(10px);
            padding:.8rem .9rem; box-shadow:0 16px 32px -20px rgba(42,33,80,.45);
        }
        .lm3-loc p { margin:0; font-size:.68rem; font-weight:700; color:#7C7399; letter-spacing:.02em; text-transform:uppercase; }
        .lm3-loc strong { display:block; margin-top:.2rem; font-size:.95rem; color:var(--lm3-ink); }
        .lm3-marker {
            width:48px; height:60px; cursor:pointer; border:0; background:transparent; padding:0;
            filter: drop-shadow(0 10px 14px rgba(49,24,110,.28));
            transition: transform .28s cubic-bezier(.2,.8,.2,1);
        }
        .lm3-marker.is-selected { transform: translateY(-8px) scale(1.14); z-index:2; }
        .lm3-marker.is-float { animation: lm3Float 2.8s ease-in-out infinite; }
        .lm3-marker svg { display:block; width:48px; height:60px; }
        .lm3-collection {
            display:flex; flex-direction:column; align-items:flex-start; gap:.12rem;
            min-width:7.2rem; max-width:9.5rem; border:0; cursor:pointer; text-align:start;
            border-radius:1rem; padding:.45rem .6rem .5rem;
            background: rgba(255,255,255,.94); color: var(--lm3-ink);
            box-shadow: 0 12px 26px -14px rgba(49,24,110,.45), 0 0 0 1px rgba(113,56,245,.18);
            backdrop-filter: blur(8px);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .lm3-collection--cluster {
            background: linear-gradient(145deg, rgba(255,255,255,.95), rgba(238,242,255,.95));
            box-shadow: 0 12px 26px -14px rgba(79,70,229,.4), 0 0 0 1px rgba(99,102,241,.25);
        }
        .lm3-collection__name {
            font:800 .72rem/1.15 ui-sans-serif,system-ui,sans-serif;
            max-width:8.6rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            unicode-bidi: isolate;
        }
        .lm3-collection__meta {
            font:600 .62rem/1.1 ui-sans-serif,system-ui,sans-serif; color:#6B6280;
        }
        .lm3-collection__meta strong { color: var(--lm3-purple); font-weight:800; }
        .lm3-collection__cta {
            margin-top:.15rem; font:800 .58rem/1 ui-sans-serif,system-ui,sans-serif;
            color:#fff; background:linear-gradient(135deg,#8B5CF6,#7138F5);
            border-radius:.55rem; padding:.22rem .45rem;
        }
        .lm3-collection.is-selected, .lm3-collection.is-pulse {
            transform: translateY(-3px) scale(1.04);
            box-shadow: 0 16px 28px -12px rgba(113,56,245,.55), 0 0 0 2px rgba(255,118,46,.35);
        }
        @keyframes lm3Float { 0%,100%{ transform: translateY(0);} 50%{ transform: translateY(-8px);} }
        @media (prefers-reduced-motion: reduce) {
            .lm3-marker.is-float, .lm3-collection.is-pulse { animation: none; }
        }
        .lm3-card {
            position:absolute; z-index:6; width:min(19rem, calc(100% - 1.5rem));
            border-radius:1.25rem; overflow:hidden;
            background: rgba(255,255,255,.95); backdrop-filter: blur(12px);
            border:1px solid rgba(255,255,255,.8);
            box-shadow:0 28px 50px -26px rgba(42,33,80,.55);
            transform: translateY(10px) scale(.98); opacity:0;
            transition: opacity .3s ease, transform .3s cubic-bezier(.2,.8,.2,1);
            pointer-events:none;
        }
        .lm3-card.is-open { opacity:1; transform: translateY(0) scale(1); pointer-events:auto; }
        .lm3-card img { width:100%; height:8rem; object-fit:cover; display:block; }
        .lm3-card__fallback {
            height:8rem; display:flex; align-items:center; justify-content:center; font-size:2.6rem;
            background: linear-gradient(135deg, var(--s), #fff);
        }
        .lm3-card__body { padding:.85rem .95rem 1rem; }
        .lm3-card__cat {
            display:inline-flex; align-items:center; gap:.3rem; border-radius:9999px;
            padding:.18rem .6rem; font-size:.65rem; font-weight:800;
        }
        .lm3-card h3 { margin:.5rem 0 .3rem; font-size:1rem; font-weight:800; color:var(--lm3-ink); line-height:1.25; unicode-bidi:isolate; }
        .lm3-card__meta { margin:0; font-size:.7rem; color:#6B6280; font-weight:600; unicode-bidi:isolate; }
        .lm3-card__cta {
            display:inline-flex; align-items:center; gap:.35rem; margin-top:.75rem;
            border-radius:.85rem; background:linear-gradient(135deg,#8B5CF6,#7138F5);
            color:#fff; font-size:.75rem; font-weight:800; padding:.5rem .85rem; text-decoration:none;
            box-shadow:0 10px 18px -10px rgba(113,56,245,.7);
        }
        .lm3-card__cta::after { content:"→"; }
        [dir="rtl"] .lm3-card__cta::after { content:"←"; }
        .lm3-city-panel {
            position:absolute; z-index:7; inset-inline-start:.85rem; bottom:.85rem;
            width:min(20rem, calc(100% - 5.5rem)); max-height:46%;
            display:flex; flex-direction:column;
            border-radius:1.15rem; overflow:hidden;
            background:rgba(255,255,255,.95); backdrop-filter:blur(12px);
            border:1px solid rgba(255,255,255,.85);
            box-shadow:0 22px 40px -24px rgba(42,33,80,.55);
        }
        .lm3-city-panel__head {
            display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem;
            padding:.7rem .8rem; border-bottom:1px solid #EEF0F7;
        }
        .lm3-city-panel__head p { margin:0; font-size:.62rem; font-weight:800; color:#7C7399; text-transform:uppercase; }
        .lm3-city-panel__head strong { display:block; font-size:.92rem; color:var(--lm3-ink); unicode-bidi:isolate; }
        .lm3-city-panel__head span { display:block; margin-top:.15rem; font-size:.65rem; color:#6B6280; font-weight:600; }
        .lm3-city-panel__head button {
            border:0; background:#F1EBFF; color:#7138F5; width:1.8rem; height:1.8rem;
            border-radius:.65rem; font-weight:800; cursor:pointer;
        }
        .lm3-city-panel__list { overflow:auto; padding:.35rem; }
        .lm3-city-panel__row {
            width:100%; display:flex; align-items:center; gap:.55rem; text-align:start;
            border:0; background:transparent; border-radius:.85rem; padding:.45rem .5rem; cursor:pointer;
        }
        .lm3-city-panel__row:hover, .lm3-city-panel__row.is-active { background:#F6F3FF; }
        .lm3-city-panel__emoji {
            width:2.1rem; height:2.1rem; border-radius:.75rem; display:grid; place-items:center;
            background:#F1EBFF; flex-shrink:0;
        }
        .lm3-city-panel__body { min-width:0; }
        .lm3-city-panel__body strong {
            display:block; font-size:.78rem; color:var(--lm3-ink); white-space:nowrap;
            overflow:hidden; text-overflow:ellipsis; unicode-bidi:isolate;
        }
        .lm3-city-panel__body em {
            display:block; margin-top:.1rem; font-style:normal; font-size:.62rem; color:#6B6280;
            font-weight:600; unicode-bidi:isolate;
        }
        .lm3-city-panel__empty { margin:0; padding:.6rem; font-size:.75rem; color:#6B6280; }
        .lm3-strip { margin-top:1rem; display:flex; gap:.85rem; overflow-x:auto; padding-bottom:.4rem; scroll-snap-type:x mandatory; }
        .lm3-strip__card {
            flex:0 0 15rem; border-radius:1.15rem; border:1px solid #E8EAF2; background:#fff;
            overflow:hidden; text-align:start; cursor:pointer; padding:0;
            box-shadow:0 12px 28px -22px rgba(42,33,80,.45); scroll-snap-align:start;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .lm3-strip__card.is-active, .lm3-strip__card:hover {
            transform: translateY(-2px); border-color:#C4B5FD;
            box-shadow:0 18px 34px -20px rgba(113,56,245,.45);
        }
        .lm3-strip__media {
            height:6.75rem; display:flex; align-items:center; justify-content:center;
            background:linear-gradient(135deg,var(--s,#F1EBFF),#fff); font-size:2rem;
        }
        .lm3-strip .pad { padding:.7rem .8rem .85rem; }
        .lm3-strip__cat { font-size:.65rem; font-weight:800; }
        .lm3-strip h4 { margin:.2rem 0 0; font-size:.82rem; font-weight:800; color:var(--lm3-ink); unicode-bidi:isolate; }
        .lm3-strip p { margin:.25rem 0 0; font-size:.65rem; color:#6B6280; font-weight:600; unicode-bidi:isolate; }
        .lm3-status { font-size:.72rem; font-weight:700; color:#6B6280; }
        @media (max-width: 640px) {
            .lm3-stage { height: 280px; min-height: 250px; }
            .lm3-loc { max-width: 11.5rem; padding:.55rem .65rem; }
            .lm3-loc strong { font-size:.8rem; }
            .lm3-card { width: min(16.5rem, calc(100% - 1rem)); }
            .lm3-city-panel { width: min(17rem, calc(100% - 4.2rem)); max-height:42%; }
            .lm3-collection { min-width:6.4rem; max-width:7.8rem; }
        }
    </style>
@endpush

@section('content')
<div class="lm3-shell mx-auto max-w-[1500px]"
     data-living-map-v3
     data-style-url="{{ $styleUrl }}"
     data-attribution="{{ $attribution }}"
     data-locale="{{ app()->getLocale() }}"
     data-events-url="{{ route('events.index') }}">
    <div class="lm3-banner mb-4" role="status">
        Living Map <strong>V3.2</strong> — event-first + Arabic RTL shaping (preview only).
        Production Home / Discover maps unchanged until owner approval.
    </div>

    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-[clamp(1.5rem,3vw,2rem)] font-extrabold tracking-tight text-[#19213D]">
                {{ __('Explore Around You') }}
                <span class="text-[#FF762E]">· {{ __('Living Map') }}</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Event-first cinematic discovery · real venues & honest city collections.') }}</p>
        </div>
        <p class="lm3-status" data-lm3-status>Initializing…</p>
    </div>

    <div class="lm3-stage" data-lm3-stage>
        <div class="lm3-map" data-lm3-map role="application" aria-label="Living Map V3.2 preview"></div>
        <div class="lm3-atmosphere" aria-hidden="true"></div>

        <div class="lm3-loc" data-lm3-loc>
            <p>{{ __('Focus') }}</p>
            <strong data-lm3-focus-label dir="auto">{{ __('Featured activity region') }}</strong>
            <p class="mt-1 normal-case tracking-normal" data-lm3-focus-sub dir="auto">{{ __('Composing from live events…') }}</p>
        </div>

        <div class="lm3-hud" aria-label="{{ __('Map controls') }}">
            <button type="button" data-lm3-tour title="{{ __('Play / pause showcase') }}" aria-pressed="false">▶</button>
            <button type="button" data-lm3-prev title="{{ __('Previous event') }}">‹</button>
            <button type="button" data-lm3-next title="{{ __('Next event') }}">›</button>
            <button type="button" data-lm3-zoom-in title="{{ __('Zoom in') }}">+</button>
            <button type="button" data-lm3-zoom-out title="{{ __('Zoom out') }}">−</button>
            <button type="button" data-lm3-reset title="{{ __('Reset featured framing') }}">↻</button>
        </div>

        <aside class="lm3-city-panel" data-lm3-city-panel hidden aria-live="polite"></aside>

        <article class="lm3-card" data-lm3-card hidden>
            <div data-lm3-card-media></div>
            <div class="lm3-card__body">
                <span class="lm3-card__cat" data-lm3-card-cat></span>
                <h3 data-lm3-card-title dir="auto"></h3>
                <p class="lm3-card__meta" data-lm3-card-meta dir="auto"></p>
                <a class="lm3-card__cta" data-lm3-card-link href="#">{{ __('View Event') }}</a>
            </div>
        </article>
    </div>

    <div class="mt-4 flex items-center justify-between gap-3">
        <h2 class="text-base font-extrabold text-[#19213D]" data-lm3-strip-title dir="auto">{{ __('Events near you') }}</h2>
        <a href="{{ route('events.index', ['view' => 'map']) }}" class="text-xs font-bold text-[#7138F5] no-underline">{{ __('Open production Discover') }} →</a>
    </div>
    <div class="lm3-strip fancy-scroll" data-lm3-strip></div>
</div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js" crossorigin></script>
    {{-- MapLibre 4.7: setRTLTextPlugin(url, lazy). Start early so Arabic glyphs are ready before the map paints. --}}
    <script>
        (function () {
            try {
                if (window.maplibregl && typeof window.maplibregl.setRTLTextPlugin === 'function') {
                    window.maplibregl.setRTLTextPlugin(
                        'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.2.3/mapbox-gl-rtl-text.js',
                        false
                    );
                }
            } catch (e) { /* ensureRtlTextPlugin retries in the module */ }
        })();
    </script>
    @vite(['resources/js/living-map-v3.js'])
@endpush
