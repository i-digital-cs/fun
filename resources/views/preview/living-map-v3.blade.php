@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Living Map V3.1 Preview')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
    <style>
        .lm3-shell {
            --lm3-purple:#7138F5; --lm3-orange:#FF762E; --lm3-canvas:#F8F9FE;
            --lm3-sky:#6EB6FF; --lm3-ink:#2A2150;
        }
        .lm3-banner {
            border-radius: 1rem; border: 1px solid #fcd34d; background: linear-gradient(90deg,#fffbeb,#fef3c7);
            padding: .85rem 1rem; color: #92400e; font-size: .8125rem; font-weight: 600;
        }
        .lm3-stage {
            position: relative; overflow: hidden; border-radius: 1.5rem;
            border: 1px solid rgba(232,234,242,.9); background: linear-gradient(160deg,#DCECFF 0%, #F8F1FF 55%, #FFE9D6 100%);
            box-shadow: 0 28px 60px -30px rgba(49,24,110,.55); height: min(64vh, 580px); min-height: 360px;
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
            position:absolute; z-index:5; display:flex; flex-direction:column; gap:.5rem;
            inset-inline-end: .85rem; top: .85rem;
        }
        .lm3-hud button {
            width:2.6rem; height:2.6rem; border-radius:.95rem; border:1px solid rgba(255,255,255,.7);
            background:rgba(255,255,255,.92); color:#19213D; font-weight:800;
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
        .lm3-agg--cluster {
            background: linear-gradient(145deg, #8B5CF6 0%, #6366F1 55%, #4F46E5 100%);
            border-radius: 1.15rem;
            min-width: 3.6rem;
        }
        .lm3-agg {
            min-width:52px; border:0; cursor:pointer; color:#fff; text-align:center;
            border-radius:1.15rem; padding:.35rem .55rem .4rem;
            background: linear-gradient(160deg,#9B6CFF 0%, #7138F5 55%, #5825D6 100%);
            box-shadow: 0 12px 24px -10px rgba(113,56,245,.7), 0 0 0 6px rgba(113,56,245,.14);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .lm3-agg__count { display:block; font:800 1rem/1.1 ui-sans-serif,system-ui,sans-serif; }
        .lm3-agg__name { display:block; margin-top:.1rem; font:700 .58rem/1.1 ui-sans-serif,system-ui,sans-serif; opacity:.92; max-width:4.6rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .lm3-agg.is-selected, .lm3-agg.is-pulse {
            transform: translateY(-4px) scale(1.08);
            box-shadow: 0 16px 28px -10px rgba(113,56,245,.8), 0 0 0 8px rgba(255,118,46,.22);
        }
        @keyframes lm3Float { 0%,100%{ transform: translateY(0);} 50%{ transform: translateY(-8px);} }
        @media (prefers-reduced-motion: reduce) {
            .lm3-marker.is-float, .lm3-agg.is-pulse { animation: none; }
        }
        .lm3-card {
            position:absolute; z-index:6; width:min(19rem, calc(100% - 1.5rem));
            border-radius:1.25rem; overflow:hidden;
            background: rgba(255,255,255,.94); backdrop-filter: blur(12px);
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
        .lm3-card h3 { margin:.5rem 0 .3rem; font-size:1rem; font-weight:800; color:var(--lm3-ink); line-height:1.25; }
        .lm3-card__meta { margin:0; font-size:.7rem; color:#6B6280; font-weight:600; }
        .lm3-card__cta {
            display:inline-flex; align-items:center; gap:.35rem; margin-top:.75rem;
            border-radius:.85rem; background:linear-gradient(135deg,#8B5CF6,#7138F5);
            color:#fff; font-size:.75rem; font-weight:800; padding:.5rem .85rem; text-decoration:none;
            box-shadow:0 10px 18px -10px rgba(113,56,245,.7);
        }
        .lm3-card__cta::after { content:"→"; }
        [dir="rtl"] .lm3-card__cta::after { content:"←"; }
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
        .lm3-strip__media img { width:100%; height:100%; object-fit:cover; }
        .lm3-strip .pad { padding:.7rem .8rem .85rem; }
        .lm3-strip__cat { font-size:.65rem; font-weight:800; }
        .lm3-strip h4 { margin:.2rem 0 0; font-size:.82rem; font-weight:800; color:var(--lm3-ink); }
        .lm3-strip p { margin:.25rem 0 0; font-size:.65rem; color:#6B6280; font-weight:600; }
        .lm3-status { font-size:.72rem; font-weight:700; color:#6B6280; }
        @media (max-width: 640px) {
            .lm3-stage { height: 250px; min-height: 230px; }
            .lm3-loc { max-width: 11.5rem; padding:.55rem .65rem; }
            .lm3-loc strong { font-size:.8rem; }
            .lm3-card { width: min(16.5rem, calc(100% - 1rem)); }
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
        Living Map <strong>V3.1</strong> visual upgrade — preview only. Production Home / Discover maps unchanged until owner approval.
        Custom Funnity MapLibre style · OpenFreeMap tiles · Explore V4 data.
    </div>

    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-[clamp(1.5rem,3vw,2rem)] font-extrabold tracking-tight text-[#19213D]">
                Explore Around You
                <span class="text-[#FF762E]">· Living Map</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500">Cinematic social discovery · framed to real activity, not empty desert.</p>
        </div>
        <p class="lm3-status" data-lm3-status>Initializing…</p>
    </div>

    <div class="lm3-stage" data-lm3-stage>
        <div class="lm3-map" data-lm3-map role="application" aria-label="Living Map V3.1 preview"></div>
        <div class="lm3-atmosphere" aria-hidden="true"></div>

        <div class="lm3-loc" data-lm3-loc>
            <p>Focus</p>
            <strong data-lm3-focus-label>Featured activity region</strong>
            <p class="mt-1 normal-case tracking-normal" data-lm3-focus-sub>Composing from live events…</p>
        </div>

        <div class="lm3-hud" aria-label="Map controls">
            <button type="button" data-lm3-tour title="Play / pause showcase" aria-pressed="false">▶</button>
            <button type="button" data-lm3-zoom-in title="Zoom in">+</button>
            <button type="button" data-lm3-zoom-out title="Zoom out">−</button>
            <button type="button" data-lm3-reset title="Reset featured framing">↻</button>
        </div>

        <article class="lm3-card" data-lm3-card hidden>
            <div data-lm3-card-media></div>
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
