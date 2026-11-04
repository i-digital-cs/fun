/**
 * Living Map localization — reads Laravel-injected data-i18n-* attributes.
 */

export function readI18n(root) {
    const get = (key, fallback = '') => {
        const v = root?.dataset?.[`i18n${key}`];
        return typeof v === 'string' && v.length ? v : fallback;
    };

    return {
        featuredRegion: get('FeaturedRegion', 'Featured activity region'),
        featuredRegionSub: get('FeaturedRegionSub', 'Event-first framing · not your GPS'),
        composing: get('Composing', 'Composing from live events…'),
        loadingRtl: get('LoadingRtl', 'Loading map text support…'),
        loadingStyle: get('LoadingStyle', 'Loading Funnity style…'),
        ready: get('Ready', 'Ready'),
        readyEventTour: get('ReadyEventTour', 'Ready · ▶ Event tour'),
        readyDiscoveryTour: get('ReadyDiscoveryTour', 'Ready · ▶ Event Discovery Tour'),
        open: get('Open', 'Open'),
        eventsWord: get('EventsWord', 'events'),
        viewEvent: get('ViewEvent', 'View Event'),
        approximateCityLocation: get('ApproximateCityLocation', 'Approximate city location'),
        approximateCityLocations: get('ApproximateCityLocations', 'Approximate city locations'),
        exactVenue: get('ExactVenue', 'Exact venue'),
        exactEventPin: get('ExactEventPin', ':title · Exact venue'),
        cityLevelLocation: get('CityLevelLocation', 'City-level location'),
        livingCitySub: get('LivingCitySub', 'Events around :city · Approximate locations'),
        eventsAround: get('EventsAround', 'Events around :city · Approximate locations'),
        aggregateEventsInCity: get(
            'AggregateEventsInCity',
            ':count events in :city · Approximate locations'
        ),
        cityClusterCount: get('CityClusterCount', ':count cities'),
        loadingCityEvents: get('LoadingCityEvents', 'Loading events…'),
        cityEventsFailed: get('CityEventsFailed', 'Could not load city events'),
        discoveryUnavailable: get('DiscoveryUnavailable', 'Discovery API unavailable'),
        maplibreFailed: get('MaplibreFailed', 'MapLibre failed to load'),
        styleFallback: get('StyleFallback', 'Style fallback'),
        reducedMotionTour: get('ReducedMotionTour', 'Reduced motion — tour disabled'),
        notEnoughForTour: get('NotEnoughForTour', 'Not enough events for a tour'),
        noEligibleEvents: get('NoEligibleEvents', 'No eligible events'),
        eventsInLabel: get('EventsInLabel', 'Events in'),
        listHeading: get('ListHeading', 'Events near you'),
        listEmpty: get('ListEmpty', 'No events in this view yet.'),
        listLoading: get('ListLoading', 'Loading events…'),
        going: get('Going', ':count going'),
        cityCluster: get('CityCluster', 'City cluster'),
        zoomExpandCluster: get('ZoomExpandCluster', 'Zoom to expand clustered cities'),
        ambientShowcase: get('AmbientShowcase', 'Ambient showcase'),
        closeCity: get('CloseCity', 'Close'),
        useMyLocation: get('UseMyLocation', 'Use my location'),
        detectingLocation: get('DetectingLocation', 'Detecting your location…'),
        gpsDenied: get('GpsDenied', 'Location permission denied.'),
        gpsUnavailable: get('GpsUnavailable', 'Could not detect your location.'),
        distanceApprox: get('DistanceApprox', '~:km km'),
        km: get('Km', 'km'),
        allDistances: get('AllDistances', 'All distances'),
        liveNow: get('LiveNow', 'Live Now'),
        withinKm: get('WithinKm', 'within :km km'),
        eventSpotlight: get('EventSpotlight', 'Event spotlight'),
        dismissSpotlight: get('DismissSpotlight', 'Dismiss spotlight'),
        spotlightApproxNote: get(
            'SpotlightApproxNote',
            'Approximate city location · not an exact venue pin'
        ),
    };
}

export function fillTemplate(template, vars = {}) {
    return String(template || '').replace(/:([a-zA-Z_]+)/g, (_, key) =>
        vars[key] != null ? String(vars[key]) : `:${key}`
    );
}
