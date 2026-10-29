/**
 * Funnity Living Map style — mutates OpenFreeMap / OpenMapTiles liberty JSON.
 * Reusable style factory (not scattered paint hacks).
 */

export const FUNNITY_PALETTE = {
    background: '#F4F0FF',
    water: '#6EB6FF',
    waterDeep: '#4F9DF0',
    land: '#FFF6EC',
    sand: '#FFE8D2',
    park: '#C8F0D8',
    wood: '#B7E4C7',
    grass: '#D4F5DE',
    building: '#F3EEFF',
    buildingExtrusion: '#E8E0FF',
    buildingOutline: 'rgba(113,56,245,0.18)',
    roadMinor: '#FFF9F2',
    roadMajor: '#FFE0C8',
    roadMotorway: '#FFC9A3',
    roadCasing: 'rgba(255,255,255,0.85)',
    border: 'rgba(113,56,245,0.22)',
    label: '#2A2150',
    labelHalo: 'rgba(255,250,245,0.92)',
    residential: '#FBF4FF',
};

/**
 * @param {object} baseStyle OpenMapTiles-compatible style JSON
 * @returns {object} Funnity-tinted style
 */
export function buildFunnityStyle(baseStyle) {
    const style = structuredClone(baseStyle);
    const p = FUNNITY_PALETTE;

    for (const layer of style.layers || []) {
        const id = layer.id || '';
        const type = layer.type;
        const paint = layer.paint || (layer.paint = {});

        if (id === 'background') {
            paint['background-color'] = p.background;
            continue;
        }

        if (id === 'water' || id.startsWith('waterway_')) {
            if (type === 'fill') paint['fill-color'] = p.water;
            if (type === 'line') paint['line-color'] = p.waterDeep;
            continue;
        }

        if (id.includes('water_name') || id.includes('waterway_line_label')) {
            if (paint['text-color'] !== undefined) paint['text-color'] = p.waterDeep;
            if (paint['text-halo-color'] !== undefined) paint['text-halo-color'] = p.labelHalo;
            continue;
        }

        if (id === 'landcover_sand') {
            paint['fill-color'] = p.sand;
            paint['fill-opacity'] = 0.55;
            continue;
        }

        if (id === 'landcover_grass' || id === 'landuse_pitch' || id === 'landuse_track') {
            paint['fill-color'] = p.grass;
            continue;
        }

        if (id === 'landcover_wood' || id === 'landcover_wetland') {
            paint['fill-color'] = p.wood;
            continue;
        }

        if (id === 'park' || id.startsWith('park')) {
            if (type === 'fill') paint['fill-color'] = p.park;
            if (type === 'line') paint['line-color'] = '#8FD9B0';
            continue;
        }

        if (id === 'landuse_residential') {
            paint['fill-color'] = p.residential;
            paint['fill-opacity'] = 0.65;
            continue;
        }

        if (id === 'building') {
            paint['fill-color'] = p.building;
            paint['fill-outline-color'] = p.buildingOutline;
            // Keep flat fill for mid zooms; extrusion takes over higher.
            continue;
        }

        if (id === 'building-3d') {
            // Soft decorative extrusion using tile render_height when present.
            layer.minzoom = 12;
            paint['fill-extrusion-color'] = p.buildingExtrusion;
            paint['fill-extrusion-opacity'] = 0.72;
            paint['fill-extrusion-vertical-gradient'] = true;
            continue;
        }

        if (id.includes('boundary') || id.includes('admin')) {
            if (type === 'line') {
                paint['line-color'] = p.border;
                paint['line-opacity'] = 0.35;
                paint['line-width'] = 0.6;
            }
            continue;
        }

        if (id.includes('road_motorway') && !id.includes('casing') && type === 'line') {
            paint['line-color'] = p.roadMotorway;
            continue;
        }
        if ((id.includes('road_trunk') || id.includes('road_primary')) && !id.includes('casing') && type === 'line') {
            paint['line-color'] = p.roadMajor;
            continue;
        }
        if (
            (id.includes('road_secondary') ||
                id.includes('road_tertiary') ||
                id.includes('road_minor') ||
                id.includes('road_link') ||
                id.includes('road_service')) &&
            !id.includes('casing') &&
            type === 'line'
        ) {
            paint['line-color'] = p.roadMinor;
            continue;
        }
        if (id.includes('casing') && type === 'line') {
            paint['line-color'] = p.roadCasing;
            continue;
        }

        if (type === 'symbol') {
            if (paint['text-color'] !== undefined) paint['text-color'] = p.label;
            if (paint['text-halo-color'] !== undefined) paint['text-halo-color'] = p.labelHalo;
            if (paint['text-halo-width'] !== undefined) {
                paint['text-halo-width'] = Math.max(Number(paint['text-halo-width']) || 1, 1.2);
            }
        }
    }

    style.name = 'Funnity Living Map';
    style.metadata = {
        ...(style.metadata || {}),
        'funnity:theme': 'living-map-v3.1',
        'funnity:base': 'openfreemap-liberty',
    };

    return style;
}

/**
 * Fetch base style and return Funnity variant.
 * @param {string} styleUrl
 */
export async function loadFunnityStyle(styleUrl) {
    const res = await fetch(styleUrl, { credentials: 'omit' });
    if (!res.ok) throw new Error(`style fetch ${res.status}`);
    const json = await res.json();
    return buildFunnityStyle(json);
}
