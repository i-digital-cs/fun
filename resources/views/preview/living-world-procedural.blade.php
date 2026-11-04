@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Living World Procedural 3D POC')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
    @vite(['resources/css/living-world-procedural.css'])
@endpush

@section('content')
    <div class="lwp-preview mx-auto max-w-[1560px] px-1 sm:px-0">
        <div class="lwp-banner" role="status">
            Living World <strong>Procedural 3D</strong> feasibility POC — Cairo downtown only.
            Real OSM footprints + artistic façades/roofs/trees. Preview only · V3.4 / V4 / V5 preserved.
        </div>

        <div class="lwp-shell"
             data-living-world-procedural
             data-mode="procedural"
             data-style-url="{{ $styleUrl }}"
             data-attribution="{{ $attribution }}"
             data-locale="{{ app()->getLocale() }}">

            <div class="lwp-head">
                <div class="min-w-0">
                    <h1>Living World <em>· Procedural POC</em></h1>
                    <p>
                        Same Cairo camera for A/B comparison. Procedural details are visualization only —
                        footprints and event coordinates stay geographic.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="lwp-modes" role="group" aria-label="Render mode">
                        <button type="button" data-lwp-mode="procedural" aria-pressed="true">Procedural 3D</button>
                        <button type="button" data-lwp-mode="extrusion" aria-pressed="false">V5 extrusion</button>
                    </div>
                    <p class="lwp-status" data-lwp-status>Loading…</p>
                </div>
            </div>

            <div class="lwp-layout">
                <div class="lwp-stage" data-lwp-stage>
                    <div class="lwp-map" data-lwp-map role="application" aria-label="Procedural 3D Cairo POC"></div>
                    <div class="lwp-hud">
                        <button type="button" data-lwp-reset aria-label="Reset camera">⌖</button>
                    </div>
                    <p class="lwp-disclaimer">
                        Artistic façades / roofs / trees on real building footprints · not surveyed architecture
                    </p>
                </div>
                <aside class="lwp-metrics" data-lwp-metrics aria-live="polite">
                    <h3>POC metrics</h3>
                    <p class="lwp-metrics__note">Waiting for map…</p>
                </aside>
            </div>

            <p class="lwp-compare">
                Compare against V5 preview (<code>/preview/living-world-v5</code>) and design reference
                <code>docs/Explore Around You Events Dashboard.png</code>.
                Tiles: OpenFreeMap · OpenMapTiles · © OpenStreetMap.
                Three.js r160 (CDN) · MapLibre 4.7.1.
            </p>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js" crossorigin></script>
    <script src="https://unpkg.com/three@0.160.0/build/three.min.js" crossorigin></script>
    <script>
        (function () {
            try {
                if (window.maplibregl && typeof window.maplibregl.setRTLTextPlugin === 'function') {
                    window.maplibregl.setRTLTextPlugin(
                        'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.2.3/mapbox-gl-rtl-text.js',
                        false
                    );
                }
            } catch (e) { /* ignore */ }
        })();
    </script>
    @vite(['resources/js/living-world-procedural.js'])
@endpush
