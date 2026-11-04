/**
 * Living World V5 — cinematic geographic style (preview / feasibility only).
 * Remaps OpenFreeMap Liberty / OpenMapTiles. Continuous real geography — not a city plate.
 */

export const FUNNITY_PALETTE_V5 = {
    background: '#E6E0FF',
    water: '#4FA0FF',
    waterDeep: '#2F7FE8',
    land: '#FFF6EC',
    sand: '#FFD9B8',
    park: '#A8E8BE',
    wood: '#8FD6A8',
    grass: '#B8ECC8',
    building: '#F2ECFF',
    buildingExtrusion: '#E4D8FF',
    buildingExtrusionTop: '#F7F2FF',
    buildingOutline: 'rgba(113,56,245,0.28)',
    roadMinor: '#FFF9F2',
    roadMajor: '#FFCFA8',
    roadMotorway: '#FFA878',
    roadCasing: 'rgba(255,255,255,0.95)',
    border: 'rgba(113,56,245,0.16)',
    label: '#1A1438',
    labelHalo: 'rgba(255,252,255,0.96)',
    residential: '#F3ECFF',
};

/**
 * Visualization-only height when OSM has no usable render_height.
 * Clearly not surveyed building height.
 */
export const VISUAL_HEIGHT_FALLBACK_M = 11;

/**
 * @param {object} baseStyle
 * @returns {object}
 */
export function buildFunnityStyleV5(baseStyle) {
    const style = structuredClone(baseStyle);
    const p = FUNNITY_PALETTE_V5;

    for (const layer of style.layers || []) {
        const id = layer.id || '';
        const type = layer.type;
        const paint = layer.paint || (layer.paint = {});

        if (id === 'background') {
            paint['background-color'] = p.background;
            continue;
        }

        if (id === 'water' || id.startsWith('waterway_')) {
            if (type === 'fill') {
                paint['fill-color'] = p.water;
                paint['fill-opacity'] = 0.95;
            }
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
            paint['fill-opacity'] = 0.7;
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
            if (type === 'line') paint['line-color'] = '#6FCB98';
            continue;
        }

        if (id === 'landuse_residential') {
            paint['fill-color'] = p.residential;
            paint['fill-opacity'] = 0.75;
            continue;
        }

        // Flat building fill only below extrusion zoom.
        if (id === 'building') {
            layer.maxzoom = 14;
            paint['fill-color'] = p.building;
            paint['fill-outline-color'] = p.buildingOutline;
            paint['fill-opacity'] = 0.9;
            continue;
        }

        if (id === 'building-3d') {
            // Earlier than stock Liberty (14) so mid-city framing still shows massing.
            layer.minzoom = 12.5;
            paint['fill-extrusion-color'] = [
                'interpolate',
                ['linear'],
                ['zoom'],
                12.5,
                p.buildingExtrusion,
                16,
                p.buildingExtrusionTop,
            ];
            paint['fill-extrusion-opacity'] = 0.88;
            paint['fill-extrusion-vertical-gradient'] = true;
            // Prefer OSM-derived render_height; else visualization fallback (not surveyed).
            paint['fill-extrusion-height'] = [
                'case',
                ['>', ['coalesce', ['to-number', ['get', 'render_height']], 0], 0],
                ['to-number', ['get', 'render_height']],
                VISUAL_HEIGHT_FALLBACK_M,
            ];
            paint['fill-extrusion-base'] = [
                'case',
                ['>', ['coalesce', ['to-number', ['get', 'render_min_height']], 0], 0],
                ['to-number', ['get', 'render_min_height']],
                0,
            ];
            continue;
        }

        if (id.includes('boundary') || id.includes('admin')) {
            if (type === 'line') {
                paint['line-color'] = p.border;
                paint['line-opacity'] = 0.22;
                paint['line-width'] = 0.5;
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

        // Soften highway shields so they don't compete with event pins.
        if (id.includes('highway-shield') || id.includes('road_label')) {
            if (type === 'symbol') {
                const layout = layer.layout || (layer.layout = {});
                if (id.includes('highway-shield')) {
                    layout.visibility = 'none';
                }
            }
        }

        if (type === 'symbol') {
            if (paint['text-color'] !== undefined) paint['text-color'] = p.label;
            if (paint['text-halo-color'] !== undefined) paint['text-halo-color'] = p.labelHalo;
            if (paint['text-halo-width'] !== undefined) {
                paint['text-halo-width'] = Math.max(Number(paint['text-halo-width']) || 1, 1.7);
            }
        }
    }

    style.name = 'Funnity Living World V5';
    style.metadata = {
        ...(style.metadata || {}),
        'funnity:theme': 'living-world-v5',
        'funnity:base': 'openfreemap-liberty',
        'funnity:height-fallback-m': VISUAL_HEIGHT_FALLBACK_M,
        'funnity:height-note': 'fallback heights are visualization-only, not surveyed',
    };

    return style;
}

/**
 * @param {string} styleUrl
 */
export async function loadFunnityStyleV5(styleUrl) {
    const res = await fetch(styleUrl, { credentials: 'omit' });
    if (!res.ok) throw new Error(`style fetch ${res.status}`);
    const json = await res.json();
    return buildFunnityStyleV5(json);
}
