@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Living Map V4.0 Preview')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin>
    @vite(['resources/css/living-map-v4.css'])
    @if (str_starts_with(app()->getLocale(), 'ar'))
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800&display=swap" rel="stylesheet">
    @endif
@endpush

@section('content')
    @php
        $radiusOptions = array_values(array_map('intval', config('location.distances_km', [5, 10, 25, 50, 100, 0])));
        $kmAttr = (int) $distanceKm;
        $eventsUrl = route('events.index');
    @endphp

    <div class="lm4-preview mx-auto max-w-[1560px] px-1 sm:px-0">
        <div class="lm4-preview__glow" aria-hidden="true"></div>

        <div class="lm4-banner" role="status">
            Living Map <strong>V4.0</strong> — cinematic hybrid (Discover scene + MapLibre Navigate).
            Isolated preview only. Production Home remains on V3.4 · V3.5.x preserved.
        </div>

        <div class="lm4-shell is-view-map is-stage-discover"
             data-living-map-v4
             data-mode="preview"
             data-style-url="{{ $styleUrl }}"
             data-attribution="{{ $attribution }}"
             data-locale="{{ app()->getLocale() }}"
             data-events-url="{{ $eventsUrl }}"
             data-reverse-url="{{ url('/api/places/reverse') }}"
             data-lat="{{ $latitude }}"
             data-lng="{{ $longitude }}"
             data-distance-km="{{ $kmAttr }}"
             data-discovery-mode="{{ $discoveryMode }}"
             data-location-label="{{ $locationLabel }}"
             data-city-id="{{ $cityId }}"
             data-radius-options="{{ e(json_encode($radiusOptions)) }}"
             data-authenticated="{{ auth()->check() ? '1' : '0' }}"
             data-i18n-featured-region="{{ __('living_map.featured_region') }}"
             data-i18n-composing="{{ __('living_map.composing') }}"
             data-i18n-loading-rtl="{{ __('living_map.loading_rtl') }}"
             data-i18n-loading-style="{{ __('living_map.loading_style') }}"
             data-i18n-ready="{{ __('living_map.ready') }}"
             data-i18n-view-event="{{ __('living_map.view_event') }}"
             data-i18n-approximate-city-location="{{ __('living_map.approximate_city_location') }}"
             data-i18n-approximate-city-locations="{{ __('living_map.approximate_city_locations') }}"
             data-i18n-exact-venue="{{ __('living_map.exact_venue') }}"
             data-i18n-events-around="{{ __('living_map.events_around') }}"
             data-i18n-aggregate-events-in-city="{{ __('living_map.aggregate_events_in_city') }}"
             data-i18n-discovery-unavailable="{{ __('living_map.discovery_unavailable') }}"
             data-i18n-maplibre-failed="{{ __('living_map.maplibre_failed') }}"
             data-i18n-list-heading="{{ __('living_map.events_near_you') }}"
             data-i18n-list-empty="{{ __('living_map.list_empty') }}"
             data-i18n-going="{{ __('living_map.going') }}"
             data-i18n-use-my-location="{{ __('events.use_my_location') }}"
             data-i18n-detecting-location="{{ __('location.detecting_location') }}"
             data-i18n-gps-denied="{{ __('living_map.gps_denied') }}"
             data-i18n-gps-unavailable="{{ __('living_map.gps_unavailable') }}"
             data-i18n-all-distances="{{ __('location.all_distances') }}"
             data-i18n-within-km="{{ __('living_map.within_km') }}"
             data-i18n-live-now="{{ __('living_map.live_now') }}"
             data-i18n-event-spotlight="{{ __('living_map.event_spotlight') }}"
             data-i18n-dismiss-spotlight="{{ __('living_map.dismiss_spotlight') }}"
             data-i18n-spotlight-approx-note="{{ __('living_map.spotlight_approx_note') }}"
             data-i18n-illustrated-disclaimer="{{ __('living_map.v4_illustrated_disclaimer') }}"
             data-i18n-mode-discover="{{ __('living_map.v4_mode_discover') }}"
             data-i18n-mode-navigate="{{ __('living_map.v4_mode_navigate') }}">

            <div class="lm4-head">
                <div class="min-w-0">
                    <h1 class="lm4-head__title">
                        {{ __('living_map.title') }}
                        <em>· {{ __('living_map.title_living') }}</em>
                    </h1>
                    <p class="lm4-head__sub">{{ __('living_map.v4_subtitle') }}</p>
                </div>
                <div class="lm4-head__actions">
                    <div class="lm4-stage-toggle" role="group" aria-label="{{ __('living_map.v4_stage_modes') }}">
                        <button type="button" data-lm-stage-mode="discover" aria-pressed="true">{{ __('living_map.v4_mode_discover') }}</button>
                        <button type="button" data-lm-stage-mode="navigate" aria-pressed="false">{{ __('living_map.v4_mode_navigate') }}</button>
                    </div>
                    <div class="lm4-modes" role="tablist" aria-label="{{ __('living_map.view_modes') }}">
                        <button type="button" role="tab" data-lm-view="map" aria-pressed="true">{{ __('living_map.mode_map') }}</button>
                        <button type="button" role="tab" data-lm-view="list" aria-pressed="false">{{ __('living_map.mode_list') }}</button>
                        <button type="button" role="tab" data-lm-view="nearby" aria-pressed="false">{{ __('living_map.mode_nearby') }}</button>
                    </div>
                    <p class="lm4-status" data-lm-status>{{ __('living_map.composing') }}</p>
                </div>
            </div>

            <div class="lm4-stage-stack">
                <div class="lm4-stage" data-lm-stage>
                    {{-- Discover: cinematic illustrated scene --}}
                    <div class="lm4-scene" data-lm-v4-scene>
                        <img class="lm4-scene__art"
                             data-lm-v4-art
                             src="{{ asset('images/living-map/v4/port-said-cinematic.jpg') }}"
                             width="1920"
                             height="1080"
                             alt="{{ __('living_map.v4_scene_alt') }}"
                             decoding="async"
                             fetchpriority="high">
                        <div class="lm4-scene__shade" aria-hidden="true"></div>
                        <div class="lm4-arcs" data-lm-v4-arcs aria-hidden="true"></div>
                        <div class="lm4-pins" data-lm-v4-pins role="group" aria-label="{{ __('living_map.v4_scene_pins') }}"></div>
                    </div>

                    {{-- Navigate: geographic MapLibre --}}
                    <div class="lm4-map" data-lm-v4-map hidden>
                        <div class="lm-map" data-lm-map role="application" aria-label="{{ __('living_map.aria_map') }}" style="position:absolute;inset:0;"></div>
                    </div>

                    <p class="lm4-disclaimer" data-lm-v4-disclaimer dir="auto">{{ __('living_map.v4_illustrated_disclaimer') }}</p>

                    <div class="lm4-loc-panel" data-lm-loc-panel>
                        <div class="lm4-loc-panel__row">
                            <span class="lm4-loc-panel__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none"><path d="M12 22s7-7.2 7-12.2A7 7 0 1 0 5 9.8C5 14.8 12 22 12 22z" fill="#7138F5"/><circle cx="12" cy="9.5" r="2.6" fill="#fff"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <strong class="lm4-loc-panel__city" data-lm-focus-label dir="auto">{{ $locationLabel }}</strong>
                                <p class="lm4-loc-panel__sub" data-lm-focus-sub dir="auto">{{ __('living_map.composing') }}</p>
                                <p class="lm4-loc-panel__dist" data-lm-distance-readout dir="auto">
                                    {{ __('living_map.within_km', ['km' => $kmAttr]) }}
                                </p>
                            </div>
                        </div>
                        <button type="button" class="lm4-loc-panel__gps" data-lm-use-location>
                            {{ __('events.use_my_location') }}
                        </button>
                        <div class="lm4-slider" data-lm-radius-wrap>
                            <input type="range"
                                   class="lm4-slider__input"
                                   data-lm-radius-slider
                                   min="0"
                                   max="{{ max(0, count($radiusOptions) - 1) }}"
                                   step="1"
                                   value="{{ max(0, (int) array_search($kmAttr, $radiusOptions, true)) }}"
                                   aria-label="{{ __('location.discovery_distance') }}">
                            <div class="lm4-slider__labels" aria-hidden="true">
                                @foreach ($radiusOptions as $distance)
                                    <span @class(['is-active' => (int) $distance === $kmAttr])>
                                        {{ (int) $distance === 0 ? __('location.all_distances') : $distance }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="lm4-hud" role="group" aria-label="{{ __('living_map.map_controls') }}">
                        <button type="button" data-lm-zoom-in aria-label="{{ __('living_map.zoom_in') }}">+</button>
                        <button type="button" data-lm-zoom-out aria-label="{{ __('living_map.zoom_out') }}">−</button>
                        <button type="button" data-lm-reset aria-label="{{ __('living_map.reset_framing') }}">⌖</button>
                    </div>

                    <aside class="lm4-spotlight"
                           data-lm-spotlight
                           hidden
                           aria-label="{{ __('living_map.event_spotlight') }}"></aside>
                </div>
            </div>

            <section class="lm4-carousel" aria-label="{{ __('living_map.events_near_you') }}">
                <div class="lm4-carousel__head">
                    <h2 data-lm-list-heading>{{ __('living_map.events_near_you') }}</h2>
                    <a href="{{ $eventsUrl }}">{{ __('living_map.see_all_events') }}</a>
                </div>
                <div class="lm4-rail" data-lm-list></div>
                <div class="lm4-carousel__nav">
                    <button type="button" data-lm-rail-next aria-label="{{ __('living_map.next_event') }}">→</button>
                </div>
            </section>
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
    @vite(['resources/js/living-map-v4.js'])
@endpush
