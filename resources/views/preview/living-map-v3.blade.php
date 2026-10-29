@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Living Map V3 Preview')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
    <style>
        .lm3-shell { --lm3-purple:#7138F5; --lm3-orange:#FF762E; --lm3-canvas:#F8F9FE; }
        .lm3-banner {
            border-radius: 1rem; border: 1px solid #fcd34d; background: linear-gradient(90deg,#fffbeb,#fef3c7);
            padding: .85rem 1rem; color: #92400e; font-size: .8125rem; font-weight: 600;
        }
        .lm3-stage {
            position: relative; overflow: hidden; border-radius: 1.35rem;
            border: 1px solid rgba(232,234,242,.95); background: #E8F4FF;
            box-shadow: 0 24px 48px -28px rgba(25,33,61,.45); height: min(62vh, 560px); min-height: 340px;
        }
        .lm3-map { position:absolute; inset:0; }
        .lm3-map .maplibregl-canvas { outline: none; }
        .lm3-hud {
            position:absolute; z-index:3; display:flex; flex-direction:column; gap:.5rem;
            inset-inline-end: .85rem; top: .85rem;
        }
        .lm3-hud button {
            width:2.5rem; height:2.5rem; border-radius:.85rem; border:1px solid #e2e8f0;
            background:rgba(255,255,255,.94); color:#19213D; font-weight:800; box-shadow:0 8px 20px -12px rgba(15,23,42,.4);
            cursor:pointer;
        }
        .lm3-hud button[aria-pressed="true"] { background: var(--lm3-purple); color:#fff; border-color:transparent; }
        .lm3-loc {
            position:absolute; z-index:3; inset-inline-start:.85rem; top:.85rem; max-width:16rem;
            border-radius:1rem; border:1px solid #e2e8f0; background:rgba(255,255,255,.95);
            padding:.75rem .85rem; box-shadow:0 12px 28px -18px rgba(15,23,42,.4);
        }
        .lm3-loc p { margin:0; font-size:.7rem; font-weight:700; color:#64748b; }
        .lm3-loc strong { display:block; margin-top:.15rem; font-size:.9rem; color:#19213D; }
        .lm3-marker {
            width:42px; height:52px; transform: translateY(-6px);
            filter: drop-shadow(0 8px 12px rgba(49,24,110,.28));
            cursor:pointer; transition: transform .25s ease;
        }
        .lm3-marker.is-selected { transform: translateY(-10px) scale(1.12); }
        .lm3-marker.is-float { animation: lm3Float 2.8s ease-in-out infinite; }
        .lm3-marker svg { display:block; width:42px; height:52px; }
        @keyframes lm3Float { 0%,100%{ transform: translateY(-6px);} 50%{ transform: translateY(-12px);} }
        @media (prefers-reduced-motion: reduce) {
            .lm3-marker.is-float { animation: none; }
        }
        .lm3-agg {
            min-width:40px; height:40px; padding:0 8px; border-radius:9999px;
            background: linear-gradient(145deg,#8B5CF6,#7138F5); color:#fff;
            border:3px solid #fff; box-shadow:0 8px 18px -8px rgba(113,56,245,.65), 0 0 0 6px rgba(113,56,245,.12);
            display:flex; align-items:center; justify-content:center;
            font:800 12px/1 ui-sans-serif,system-ui,sans-serif; cursor:pointer;
        }
        .lm3-card {
            position:absolute; z-index:4; width:min(18.5rem, calc(100% - 1.5rem));
            border-radius:1.1rem; overflow:hidden; background:#fff;
            border:1px solid #e8eaf2; box-shadow:0 22px 44px -24px rgba(25,33,61,.55);
            transform: translateY(8px); opacity:0; transition: opacity .28s ease, transform .28s ease;
            pointer-events:none;
        }
        .lm3-card.is-open { opacity:1; transform: translateY(0); pointer-events:auto; }
        .lm3-card img { width:100%; height:7.5rem; object-fit:cover; background:#EEF2FF; }
        .lm3-card__body { padding:.75rem .85rem .9rem; }
        .lm3-card__cat { display:inline-flex; align-items:center; gap:.3rem; border-radius:9999px; padding:.15rem .55rem; font-size:.65rem; font-weight:800; }
        .lm3-card h3 { margin:.45rem 0 .25rem; font-size:.95rem; font-weight:800; color:#19213D; line-height:1.25; }
        .lm3-card__meta { margin:0; font-size:.7rem; color:#64748b; font-weight:600; }
        .lm3-card__cta {
            display:inline-flex; margin-top:.65rem; border-radius:.75rem; background:var(--lm3-purple);
            color:#fff; font-size:.72rem; font-weight:800; padding:.45rem .75rem; text-decoration:none;
        }
        .lm3-strip { margin-top:1rem; display:flex; gap:.75rem; overflow-x:auto; padding-bottom:.35rem; }
        .lm3-strip a {
            flex:0 0 14rem; border-radius:1rem; border:1px solid #e8eaf2; background:#fff;
            overflow:hidden; text-decoration:none; color:inherit; box-shadow:0 10px 24px -20px rgba(15,23,42,.4);
        }
        .lm3-strip img { width:100%; height:6.5rem; object-fit:cover; background:#F1EBFF; }
        .lm3-strip .pad { padding:.65rem .75rem .8rem; }
        .lm3-strip h4 { margin:0; font-size:.8rem; font-weight:800; color:#19213D; }
        .lm3-strip p { margin:.25rem 0 0; font-size:.65rem; color:#64748b; font-weight:600; }
        .lm3-status { font-size:.7rem; font-weight:700; color:#64748b; }
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
        Living Map V3 <strong>prototype</strong> — visual preview only. Production Home / Discover maps are unchanged until owner approval.
        Engine: MapLibre GL · Tiles: OpenFreeMap (free vector + attribution).
    </div>

    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-[clamp(1.5rem,3vw,2rem)] font-extrabold tracking-tight text-[#19213D]">
                Explore Around You
                <span class="text-[#FF762E]">· Living Map</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500">Cinematic discovery preview using live Explore V4 data.</p>
        </div>
        <p class="lm3-status" data-lm3-status>Initializing…</p>
    </div>

    <div class="lm3-stage" data-lm3-stage>
        <div class="lm3-map" data-lm3-map role="application" aria-label="Living Map V3 preview"></div>

        <div class="lm3-loc" data-lm3-loc>
            <p>Focus</p>
            <strong data-lm3-focus-label>Egypt</strong>
            <p class="mt-1" data-lm3-focus-sub>Nationwide · no auto GPS</p>
        </div>

        <div class="lm3-hud" aria-label="Map controls">
            <button type="button" data-lm3-tour title="Play / pause cinematic tour" aria-pressed="false">▶</button>
            <button type="button" data-lm3-zoom-in title="Zoom in">+</button>
            <button type="button" data-lm3-zoom-out title="Zoom out">−</button>
            <button type="button" data-lm3-reset title="Reset view">↻</button>
        </div>

        <article class="lm3-card" data-lm3-card hidden>
            <div data-lm3-card-media class="flex h-[7.5rem] items-center justify-center bg-gradient-to-br from-[#F1EBFF] to-[#FFE8D6] text-4xl"></div>
            <div class="lm3-card__body">
                <span class="lm3-card__cat" data-lm3-card-cat></span>
                <h3 data-lm3-card-title></h3>
                <p class="lm3-card__meta" data-lm3-card-meta></p>
                <a class="lm3-card__cta" data-lm3-card-link href="#">View Event</a>
            </div>
        </article>
    </div>

    <div class="mt-4 flex items-center justify-between gap-3">
        <h2 class="text-base font-extrabold text-[#19213D]">Events near you</h2>
        <a href="{{ route('events.index', ['view' => 'map']) }}" class="text-xs font-bold text-[#7138F5] no-underline">Open production Discover →</a>
    </div>
    <div class="lm3-strip fancy-scroll" data-lm3-strip></div>
</div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js" crossorigin></script>
    @vite(['resources/js/living-map-v3.js'])
@endpush
