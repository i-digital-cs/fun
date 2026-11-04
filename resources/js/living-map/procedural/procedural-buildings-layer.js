/**
 * MapLibre custom layer — procedural Three.js buildings on real OSM footprints.
 * Decorative façades/roofs/trees are artistic visualization, not surveyed features.
 */

import {
    buildProceduralBuilding,
    buildProceduralTree,
    hashStr,
    metersPerDeg,
} from './building-factory.js';

const DEFAULT_ORIGIN = { lng: 31.2357, lat: 30.0444 };

/**
 * @param {object} options
 * @param {typeof THREE} options.THREE
 * @param {object} [options.origin]
 * @param {number} [options.maxBuildings]
 * @param {boolean} [options.enableShadows]
 * @param {boolean} [options.enableTrees]
 */
export function createProceduralBuildingsLayer(options) {
    const THREE = options.THREE;
    const origin = options.origin || DEFAULT_ORIGIN;
    const maxBuildings = options.maxBuildings ?? 90;
    const enableShadows = options.enableShadows !== false;
    const enableTrees = options.enableTrees !== false;

    const state = {
        id: 'funnity-procedural-buildings',
        type: 'custom',
        renderingMode: '3d',
        visible: true,
        buildingCount: 0,
        treeCount: 0,
        buildMs: null,
        lastError: null,
        _map: null,
        _renderer: null,
        _scene: null,
        _camera: null,
        _root: null,
        _built: false,
        _modelTransform: null,
    };

    function syncTransform() {
        const mc = window.maplibregl.MercatorCoordinate.fromLngLat(
            { lng: origin.lng, lat: origin.lat },
            0
        );
        state._modelTransform = {
            translateX: mc.x,
            translateY: mc.y,
            translateZ: mc.z,
            rotateX: Math.PI / 2,
            rotateY: 0,
            rotateZ: 0,
            scale: mc.meterInMercatorCoordinateUnits(),
        };
    }

    function clearSceneBuildings() {
        if (!state._root) return;
        const keep = new Set(['lwp-ground']);
        const doomed = state._root.children.filter((c) => !keep.has(c.name));
        doomed.forEach((child) => {
            state._root.remove(child);
            child.traverse((obj) => {
                if (obj.geometry) obj.geometry.dispose?.();
                if (obj.material) {
                    if (Array.isArray(obj.material)) obj.material.forEach((m) => m.dispose?.());
                    else {
                        if (obj.material.map) obj.material.map.dispose?.();
                        obj.material.dispose?.();
                    }
                }
            });
        });
        state.buildingCount = 0;
        state.treeCount = 0;
    }

    function ringArea(ring) {
        if (!ring || ring.length < 3) return 0;
        const { mx, my } = metersPerDeg(origin.lat);
        let a = 0;
        for (let i = 0; i < ring.length - 1; i += 1) {
            const x1 = (ring[i][0] - origin.lng) * mx;
            const y1 = (ring[i][1] - origin.lat) * my;
            const x2 = (ring[i + 1][0] - origin.lng) * mx;
            const y2 = (ring[i + 1][1] - origin.lat) * my;
            a += x1 * y2 - x2 * y1;
        }
        return Math.abs(a) / 2;
    }

    function extractRing(feature) {
        const g = feature.geometry;
        if (!g) return null;
        if (g.type === 'Polygon') return g.coordinates?.[0] || null;
        if (g.type === 'MultiPolygon') {
            // Largest outer ring.
            let best = null;
            let bestA = -1;
            (g.coordinates || []).forEach((poly) => {
                const ring = poly?.[0];
                if (!ring) return;
                const a = ringArea(ring);
                if (a > bestA) {
                    bestA = a;
                    best = ring;
                }
            });
            return best;
        }
        return null;
    }

    /**
     * Pull footprints from vector source and rebuild meshes.
     * @param {maplibregl.Map} map
     */
    function rebuildFromMap(map) {
        if (!state._root || !state.visible) return;
        const t0 = performance.now();
        clearSceneBuildings();

        let features = [];
        try {
            features = map.querySourceFeatures('openmaptiles', {
                sourceLayer: 'building',
            });
        } catch (e) {
            state.lastError = String(e?.message || e);
            return;
        }

        // Prefer larger footprints; cap for GPU; dedupe tile overlaps.
        const seen = new Set();
        const scored = features
            .map((f, idx) => {
                const ring = extractRing(f);
                const area = ringArea(ring);
                const h = Number(f.properties?.render_height) || 0;
                const key = ring
                    ? `${ring[0]?.[0]?.toFixed?.(5)},${ring[0]?.[1]?.toFixed?.(5)}:${Math.round(area)}`
                    : String(idx);
                return { f, ring, area, h, idx, key };
            })
            .filter((row) => {
                if (!row.ring || row.area < 60) return false;
                if (seen.has(row.key)) return false;
                seen.add(row.key);
                return true;
            })
            .sort((a, b) => b.area - a.area)
            .slice(0, maxBuildings);

        scored.forEach((row) => {
            const idKey = `${row.f.id ?? row.idx}-${row.ring[0]?.[0]}-${row.ring[0]?.[1]}`;
            const height =
                row.h > 0 ? row.h : 10 + (hashStr(idKey) % 18); // visualization fallback
            const building = buildProceduralBuilding({
                THREE,
                ring: row.ring,
                originLat: origin.lat,
                originLng: origin.lng,
                heightM: height,
                idKey,
            });
            if (!building) return;
            state._root.add(building);
            state.buildingCount += 1;

            if (enableTrees && row.area > 400 && hashStr(idKey) % 4 === 0) {
                const { mx, my } = metersPerDeg(origin.lat);
                let sx = 0;
                let sy = 0;
                let n = 0;
                row.ring.forEach((c) => {
                    if (!c) return;
                    sx += (c[0] - origin.lng) * mx;
                    sy += (c[1] - origin.lat) * my;
                    n += 1;
                });
                if (n > 0) {
                    const cx = sx / n;
                    const cz = -(sy / n); // match ExtrudeGeometry rotateX mapping
                    // Place slightly offset — artistic, not a surveyed tree GPS.
                    const tree = buildProceduralTree(
                        THREE,
                        cx + 3,
                        cz + 2,
                        hashStr(`${idKey}-tree`)
                    );
                    // Tree factory uses z as third arg in XZ plane with Y up — our building verts use Z = -north after rotate.
                    // buildProceduralTree sets position (x,0,z); pass cz as z.
                    tree.position.set(cx + 2.5, 0, cz + 2);
                    state._root.add(tree);
                    state.treeCount += 1;
                }
            }
        });

        state.buildMs = Math.round(performance.now() - t0);
        state._built = true;
        state.lastError = null;
        state.sourceFeatureCount = features.length;
        state.candidateCount = scored.length;
        map.triggerRepaint();
    }

    state.onAdd = function onAdd(map, gl) {
        state._map = map;
        syncTransform();

        state._camera = new THREE.Camera();
        state._scene = new THREE.Scene();
        state._root = new THREE.Group();
        state._scene.add(state._root);

        const ambient = new THREE.AmbientLight(0xfff5eb, 0.72);
        const sun = new THREE.DirectionalLight(0xffe4c8, 1.05);
        sun.position.set(80, 140, 40);
        if (enableShadows) {
            sun.castShadow = true;
            sun.shadow.mapSize.width = 1024;
            sun.shadow.mapSize.height = 1024;
            sun.shadow.camera.near = 10;
            sun.shadow.camera.far = 400;
            sun.shadow.camera.left = -180;
            sun.shadow.camera.right = 180;
            sun.shadow.camera.top = 180;
            sun.shadow.camera.bottom = -180;
            sun.shadow.bias = -0.0005;
        }
        const fill = new THREE.DirectionalLight(0xc4b5fd, 0.35);
        fill.position.set(-60, 40, -80);
        state._scene.add(ambient, sun, fill);

        // Soft ground plane for contact shadows (artistic).
        const ground = new THREE.Mesh(
            new THREE.CircleGeometry(220, 48),
            new THREE.ShadowMaterial({ opacity: 0.22 })
        );
        ground.name = 'lwp-ground';
        ground.rotation.x = -Math.PI / 2;
        ground.position.y = 0.05;
        ground.receiveShadow = true;
        state._root.add(ground);

        state._renderer = new THREE.WebGLRenderer({
            canvas: map.getCanvas(),
            context: gl,
            antialias: true,
        });
        state._renderer.autoClear = false;
        state._renderer.outputColorSpace = THREE.SRGBColorSpace;
        if (enableShadows) {
            state._renderer.shadowMap.enabled = true;
            state._renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        }

        // Defer mesh build until tiles are present.
        const tryBuild = () => {
            if (!state.visible) return;
            rebuildFromMap(map);
            if (state.buildingCount < 8) {
                // Tiles may still be loading.
                setTimeout(tryBuild, 600);
            }
        };
        map.once('idle', tryBuild);
    };

    state.render = function render(_gl, args) {
        if (!state.visible || !state._renderer || !state._modelTransform) return;
        const t = state._modelTransform;
        const rotationX = new THREE.Matrix4().makeRotationAxis(new THREE.Vector3(1, 0, 0), t.rotateX);
        const rotationY = new THREE.Matrix4().makeRotationAxis(new THREE.Vector3(0, 1, 0), t.rotateY);
        const rotationZ = new THREE.Matrix4().makeRotationAxis(new THREE.Vector3(0, 0, 1), t.rotateZ);

        const rawMatrix =
            args?.defaultProjectionData?.mainMatrix ||
            (Array.isArray(args) || ArrayBuffer.isView?.(args) ? args : null);
        if (!rawMatrix) return;

        const m = new THREE.Matrix4().fromArray(rawMatrix);
        const l = new THREE.Matrix4()
            .makeTranslation(t.translateX, t.translateY, t.translateZ)
            .scale(new THREE.Vector3(t.scale, -t.scale, t.scale))
            .multiply(rotationX)
            .multiply(rotationY)
            .multiply(rotationZ);

        state._camera.projectionMatrix = m.multiply(l);
        state._renderer.resetState();
        state._renderer.render(state._scene, state._camera);
    };

    state.onRemove = function onRemove() {
        clearSceneBuildings();
        state._renderer = null;
        state._scene = null;
        state._camera = null;
        state._root = null;
        state._map = null;
    };

    state.setVisible = function setVisible(v) {
        state.visible = !!v;
        if (state._root) state._root.visible = state.visible;
        state._map?.triggerRepaint();
    };

    state.rebuild = function rebuild() {
        if (state._map) rebuildFromMap(state._map);
    };

    state.getStats = function getStats() {
        return {
            buildingCount: state.buildingCount,
            treeCount: state.treeCount,
            buildMs: state.buildMs,
            sourceFeatureCount: state.sourceFeatureCount ?? 0,
            candidateCount: state.candidateCount ?? 0,
            maxBuildings,
            enableShadows,
            enableTrees,
            lastError: state.lastError,
            visible: state.visible,
        };
    };

    return state;
}
