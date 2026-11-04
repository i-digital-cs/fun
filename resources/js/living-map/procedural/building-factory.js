/**
 * Procedural building meshes from real OSM footprints (artistic visualization).
 * Expects global THREE (CDN). Geographic rings are lng/lat; heights in meters.
 */

/** Deterministic 32-bit hash for stable facade/roof choices. */
export function hashStr(s) {
    let h = 2166136261;
    const str = String(s);
    for (let i = 0; i < str.length; i += 1) {
        h ^= str.charCodeAt(i);
        h = Math.imul(h, 16777619);
    }
    return h >>> 0;
}

const FACADE_PALETTE = [
    0xf2ecff, // lavender
    0xfff6ec, // warm ivory
    0xffe0c4, // sand
    0xe8f0ff, // cool mist
    0xfce7f3, // soft pink
    0xe8fff4, // mint wash
    0xffe8d6, // peach
];

const ROOF_PALETTE = [
    0xc4b5fd, // purple roof tint
    0xfda4af, // coral
    0xfbbf24, // amber
    0x94a3b8, // slate
    0xa7f3d0, // mint
];

/**
 * Approximate meters-per-degree at latitude (planar local frame).
 * @param {number} lat
 */
export function metersPerDeg(lat) {
    const cos = Math.cos((lat * Math.PI) / 180);
    return {
        mx: 111320 * cos,
        my: 110540,
    };
}

/**
 * Build a simple window-grid canvas texture (shared cache by color).
 * @param {typeof THREE} THREE
 * @param {number} facadeHex
 */
const textureCache = new Map();
export function facadeTexture(THREE, facadeHex) {
    const key = facadeHex.toString(16);
    if (textureCache.has(key)) return textureCache.get(key);

    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    const r = (facadeHex >> 16) & 255;
    const g = (facadeHex >> 8) & 255;
    const b = facadeHex & 255;
    ctx.fillStyle = `rgb(${r},${g},${b})`;
    ctx.fillRect(0, 0, size, size);

    // Window grid — decorative only.
    const winW = 8;
    const winH = 10;
    const gapX = 6;
    const gapY = 8;
    for (let y = 8; y < size - 6; y += winH + gapY) {
        for (let x = 6; x < size - 6; x += winW + gapX) {
            const lit = (x * 3 + y * 7 + facadeHex) % 5 !== 0;
            ctx.fillStyle = lit ? 'rgba(255,248,220,0.55)' : 'rgba(60,50,90,0.28)';
            ctx.fillRect(x, y, winW, winH);
        }
    }

    const tex = new THREE.CanvasTexture(canvas);
    tex.wrapS = THREE.RepeatWrapping;
    tex.wrapT = THREE.RepeatWrapping;
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.anisotropy = 2;
    textureCache.set(key, tex);
    return tex;
}

/**
 * @param {object} opts
 * @param {typeof THREE} opts.THREE
 * @param {number[][]} opts.ring lng/lat ring (closed or open)
 * @param {number} opts.originLat
 * @param {number} opts.originLng
 * @param {number} opts.heightM
 * @param {string} opts.idKey
 * @returns {THREE.Group|null}
 */
export function buildProceduralBuilding({ THREE, ring, originLat, originLng, heightM, idKey }) {
    try {
        if (!ring || ring.length < 3) return null;
        const { mx, my } = metersPerDeg(originLat);
        const h = Math.max(6, Math.min(Number(heightM) || 12, 220));
        const seed = hashStr(idKey || JSON.stringify(ring[0]));
        const facadeHex = FACADE_PALETTE[seed % FACADE_PALETTE.length];
        const roofHex = ROOF_PALETTE[(seed >>> 8) % ROOF_PALETTE.length];
        const roofStyle = seed % 3; // 0 flat, 1 slab lip, 2 low hip

        const shape = new THREE.Shape();
        let started = false;
        let minX = Infinity;
        let maxX = -Infinity;
        let minY = Infinity;
        let maxY = -Infinity;
        const pts = [];
        // Cap ring complexity for ExtrudeGeometry stability / perf.
        const step = ring.length > 48 ? Math.ceil(ring.length / 40) : 1;

        for (let i = 0; i < ring.length; i += step) {
            const c = ring[i];
            if (!c || c.length < 2) continue;
            const lng = Number(c[0]);
            const lat = Number(c[1]);
            if (!Number.isFinite(lng) || !Number.isFinite(lat)) continue;
            const x = (lng - originLng) * mx;
            const y = (lat - originLat) * my;
            pts.push([x, y]);
            minX = Math.min(minX, x);
            maxX = Math.max(maxX, x);
            minY = Math.min(minY, y);
            maxY = Math.max(maxY, y);
            if (!started) {
                shape.moveTo(x, y);
                started = true;
            } else {
                shape.lineTo(x, y);
            }
        }
        if (!started || pts.length < 3) return null;

        // Skip tiny footprints (noise).
        const areaApprox = (maxX - minX) * (maxY - minY);
        if (areaApprox < 40) return null;

        const extrude = new THREE.ExtrudeGeometry(shape, {
            depth: h,
            bevelEnabled: false,
            curveSegments: 1,
        });
        // Shape is in XY; extrude along +Z → rotate to Y-up.
        extrude.rotateX(-Math.PI / 2);

        const spanX = Math.max(8, maxX - minX);
        const tex = facadeTexture(THREE, facadeHex);
        tex.repeat.set(Math.max(1, spanX / 12), Math.max(1, h / 10));

        const wallMat = new THREE.MeshStandardMaterial({
            map: tex,
            color: 0xffffff,
            roughness: 0.72,
            metalness: 0.05,
            flatShading: false,
        });
        const mesh = new THREE.Mesh(extrude, wallMat);
        mesh.castShadow = true;
        mesh.receiveShadow = true;

        const group = new THREE.Group();
        group.add(mesh);

        // Roof plate (decorative).
        const roofShape = shape.clone();
        const roofGeom = new THREE.ExtrudeGeometry(roofShape, {
            depth: roofStyle === 0 ? 0.6 : roofStyle === 1 ? 1.4 : 2.2,
            bevelEnabled: false,
            curveSegments: 1,
        });
        roofGeom.rotateX(-Math.PI / 2);
        const roofMat = new THREE.MeshStandardMaterial({
            color: roofHex,
            roughness: 0.85,
            metalness: 0.02,
        });
        const roof = new THREE.Mesh(roofGeom, roofMat);
        roof.position.y = h;
        roof.castShadow = true;
        roof.receiveShadow = true;
        group.add(roof);

        group.userData = {
            kind: 'building',
            heightM: h,
            facadeHex,
            roofStyle,
            artistic: true,
        };
        return group;
    } catch {
        return null;
    }
}

/**
 * Tiny procedural tree (not a surveyed plant location — decorative near footprint).
 * @param {typeof THREE} THREE
 * @param {number} x
 * @param {number} z
 * @param {number} seed
 */
export function buildProceduralTree(THREE, x, z, seed) {
    const g = new THREE.Group();
    const trunkH = 2.2 + (seed % 5) * 0.25;
    const trunk = new THREE.Mesh(
        new THREE.CylinderGeometry(0.18, 0.28, trunkH, 5),
        new THREE.MeshStandardMaterial({ color: 0x8b5a3c, roughness: 0.9 })
    );
    trunk.position.y = trunkH / 2;
    trunk.castShadow = true;
    const canopy = new THREE.Mesh(
        new THREE.SphereGeometry(1.1 + (seed % 3) * 0.15, 6, 5),
        new THREE.MeshStandardMaterial({ color: 0x34d399, roughness: 0.75 })
    );
    canopy.position.y = trunkH + 0.6;
    canopy.castShadow = true;
    g.add(trunk, canopy);
    g.position.set(x, 0, z);
    g.userData = { kind: 'vegetation', artistic: true };
    return g;
}
