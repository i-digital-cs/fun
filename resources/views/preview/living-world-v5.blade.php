@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Living World V5 Feasibility')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
    @vite(['resources/css/living-world-v5.css'])
    @if (str_starts_with(app()->getLocale(), 'ar'))
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800&display=swap" rel="stylesheet">
    @endif
@endpush

@section('content')
    <div class="lw5-preview mx-auto max-w-[1560px] px-1 sm:px-0">
        <div class="lw5-preview__glow" aria-hidden="true"></div>

        <div class="lw5-banner" role="status">
            Living World <strong>V5</strong> — real geographic cinematic MapLibre feasibility.
            Continuous Egypt tiles + OSM building extrusion. Isolated preview only.
            Production Home remains V3.4 · V3.5 / V4 preserved. No static city plates.
        </div>

        <div class="lw5-shell"
             data-living-world-v5
             data-city="{{ $initialCity }}"
             data-style-url="{{ $styleUrl }}"
             data-attribution="{{ $attribution }}"
             data-locale="{{ app()->getLocale() }}">

            <div class="lw5-head">
                <div class="min-w-0">
                    <h1 class="lw5-head__title">
                        Explore Around You
                        <em>· Living World</em>
                    </h1>
                    <p class="lw5-head__sub">
                        Real pan/zoom geography · cinematic 3D building massing · Discovery API events only.
                        Building heights may use visualization fallbacks — not surveyed elevations.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="lw5-cities" role="group" aria-label="Test cities">
                        <button type="button" data-lw-city="cairo" aria-pressed="{{ $initialCity === 'cairo' ? 'true' : 'false' }}">Cairo</button>
                        <button type="button" data-lw-city="port-said" aria-pressed="{{ $initialCity === 'port-said' ? 'true' : 'false' }}">Port Said</button>
                        <button type="button" data-lw-city="alexandria" aria-pressed="{{ $initialCity === 'alexandria' ? 'true' : 'false' }}">Alexandria</button>
                    </div>
                    <p class="lw5-status" data-lw-status>Loading…</p>
                </div>
            </div>

            <div class="lw5-layout">
                <div class="lw5-stage" data-lw-stage>
                    <div class="lw5-map" data-lw-map role="application" aria-label="Living World geographic map"></div>

                    <div class="lw5-loc">
                        <strong data-lw-city-label>
                            @if ($initialCity === 'port-said')
                                {{ app()->getLocale() === 'ar' ? 'بورسعيد' : 'Port Said' }}
                            @elseif ($initialCity === 'alexandria')
                                {{ app()->getLocale() === 'ar' ? 'الإسكندرية' : 'Alexandria' }}
                            @else
                                {{ app()->getLocale() === 'ar' ? 'القاهرة' : 'Cairo' }}
                            @endif
                        </strong>
                        <p data-lw-city-note>Dense urban core — typically richest OSM building footprints.</p>
                    </div>

                    <div class="lw5-hud" role="group" aria-label="Map controls">
                        <button type="button" data-lw-zoom-in aria-label="Zoom in">+</button>
                        <button type="button" data-lw-zoom-out aria-label="Zoom out">−</button>
                        <button type="button" data-lw-reset aria-label="Reset city framing">⌖</button>
                    </div>

                    <aside class="lw5-spotlight" data-lw-spotlight hidden aria-label="Event spotlight"></aside>

                    <p class="lw5-disclaimer" dir="auto">
                        Continuous OpenStreetMap geography · 3D massing may use visualization heights · not an illustrated city plate
                    </p>
                </div>

                <aside class="lw5-metrics" data-lw-metrics aria-live="polite">
                    <h3>Viewport building sample</h3>
                    <p class="lw5-metrics__note">Waiting for map idle…</p>
                </aside>
            </div>

            <p class="lw5-footnote">
                Tiles: OpenFreeMap · OpenMapTiles · © OpenStreetMap contributors.
                {{ $attribution }}
            </p>
        </div>
    </div>
@endsection

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
            } catch (e) { /* ignore */ }
        })();
    </script>
    @vite(['resources/js/living-world-v5.js'])
@endpush
