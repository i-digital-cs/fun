@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Mapbox Standard 3D Trial')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.css" crossorigin>
    @vite(['resources/css/mapbox-standard-trial.css'])
@endpush

@section('content')
    <div class="mbx-preview mx-auto max-w-[1560px] px-1 sm:px-0">
        <div class="mbx-banner" role="status">
            Mapbox <strong>Standard 3D</strong> provider trial — ready-made basemap only.
            Isolated preview · no Three.js · no procedural meshes · no homepage cutover.
            Production Home remains V3.4 · prior Living Map / World previews preserved.
        </div>

        <div class="mbx-shell"
             data-mapbox-standard-trial
             data-city="{{ $initialCity }}"
             data-locale="{{ app()->getLocale() }}"
             @if ($tokenConfigured) data-mapbox-token="{{ $mapboxToken }}" @endif>

            <div class="mbx-head">
                <div class="min-w-0">
                    <h1>Explore Around You <em>· Mapbox Standard</em></h1>
                    <p>
                        Official Mapbox GL JS + Standard style with 3D objects enabled.
                        Event pins and cards use the existing Discovery API only.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="mbx-cities" role="group" aria-label="Test cities">
                        <button type="button" data-mbx-city="cairo" aria-pressed="{{ $initialCity === 'cairo' ? 'true' : 'false' }}">Cairo</button>
                        <button type="button" data-mbx-city="alexandria" aria-pressed="{{ $initialCity === 'alexandria' ? 'true' : 'false' }}">Alexandria</button>
                        <button type="button" data-mbx-city="port-said" aria-pressed="{{ $initialCity === 'port-said' ? 'true' : 'false' }}">Port Said</button>
                    </div>
                    <p class="mbx-status" data-mbx-status>
                        {{ $tokenConfigured ? 'Loading…' : 'Token required' }}
                    </p>
                </div>
            </div>

            <div class="mbx-layout">
                <div class="mbx-stage" data-mbx-stage>
                    <div class="mbx-map" data-mbx-map role="application" aria-label="Mapbox Standard 3D trial map"></div>

                    <div class="mbx-gate" data-mbx-token-gate @if ($tokenConfigured) hidden @endif>
                        <div class="mbx-gate__card">
                            <h2>Mapbox public token required</h2>
                            <p>
                                No <code>MAPBOX_ACCESS_TOKEN</code> (<code>pk.*</code>) is configured on this server.
                                This trial does not create Mapbox accounts or enable paid billing.
                            </p>
                            <p>
                                Owner action: create/use a Mapbox account, add a <strong>public</strong> token to
                                <code>.env</code> as <code>MAPBOX_ACCESS_TOKEN</code>, then reload this preview.
                                Never put secret <code>sk.*</code> tokens in front-end config.
                            </p>
                        </div>
                    </div>

                    <div class="mbx-loc">
                        <strong data-mbx-city-label>
                            @if ($initialCity === 'port-said')
                                {{ app()->getLocale() === 'ar' ? 'بورسعيد' : 'Port Said' }}
                            @elseif ($initialCity === 'alexandria')
                                {{ app()->getLocale() === 'ar' ? 'الإسكندرية' : 'Alexandria' }}
                            @else
                                {{ app()->getLocale() === 'ar' ? 'القاهرة' : 'Cairo' }}
                            @endif
                        </strong>
                        <p>Mapbox Standard · Discovery events · approximate labels preserved</p>
                    </div>

                    <aside class="mbx-spotlight" data-mbx-spotlight hidden aria-label="Event spotlight"></aside>
                </div>

                <aside class="mbx-metrics" data-mbx-metrics aria-live="polite">
                    <h3>Trial metrics</h3>
                    <p class="mbx-metrics__note">
                        {{ $tokenConfigured ? 'Waiting for map idle…' : 'Blocked until a public Mapbox token is provided.' }}
                    </p>
                </aside>
            </div>

            <p class="mbx-foot">
                Mapbox GL JS v3 · style <code>mapbox://styles/mapbox/standard</code>.
                © Mapbox · © OpenStreetMap. Discovery data remains first-party Funnity.
            </p>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.js" crossorigin></script>
    @vite(['resources/js/mapbox-standard-trial.js'])
@endpush
