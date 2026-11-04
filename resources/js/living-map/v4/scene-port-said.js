/**
 * Port Said cinematic scene — illustrative hotspot layout.
 * Coordinates are scene-normalized (u,v) ∈ [0,1], NOT lat/lng.
 */
export const PORT_SAID_SCENE = {
    sceneId: 'port-said-v1',
    cityId: 11,
    cityName: 'Port Said',
    cityNameAr: 'بورسعيد',
    // Real Port Said centroid — used only for Discovery API origin / Navigate mode.
    geo: { lat: 31.2653, lng: 32.3019 },
    bbox: { north: 31.42, south: 31.1, east: 32.45, west: 32.15 },
    image: {
        src: '/images/living-map/v4/port-said-cinematic.jpg',
        width: 1920,
        height: 1080,
        alt: 'Illustrated isometric view of a Port Said–inspired coastal city',
    },
    /**
     * Decorative slots. Events are bound at runtime from the Discovery API.
     * prefer: category keyword hints for nicer visual match (optional).
     */
    hotspots: [
        { id: 'harbor', u: 0.72, v: 0.3, prefer: ['music', 'social', 'food'], label: 'Harbor' },
        { id: 'lighthouse', u: 0.82, v: 0.4, prefer: ['outdoor', 'running'], label: 'Pier' },
        { id: 'beach', u: 0.2, v: 0.44, prefer: ['outdoor', 'social'], label: 'Waterfront' },
        { id: 'plaza', u: 0.48, v: 0.46, prefer: ['cinema', 'music', 'food'], label: 'Plaza' },
        { id: 'stadium', u: 0.3, v: 0.7, prefer: ['football', 'tennis', 'sports', 'basketball'], label: 'Sports' },
        { id: 'downtown', u: 0.55, v: 0.58, prefer: ['gaming', 'playstation', 'board-games'], label: 'Downtown' },
        { id: 'park', u: 0.4, v: 0.36, prefer: ['outdoor', 'running', 'social'], label: 'Park' },
        { id: 'marina', u: 0.62, v: 0.48, prefer: ['food', 'social', 'music'], label: 'Marina' },
    ],
    /** City aggregate badge position (illustrated — not a survey pin). */
    cityBadge: { u: 0.5, v: 0.22 },
};
