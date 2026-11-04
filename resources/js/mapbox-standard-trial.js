/**
 * Mapbox Standard 3D provider trial entry (preview only).
 */
import { createMapboxStandardTrial } from './living-map/mapbox-standard-trial.js';

async function boot() {
    const root = document.querySelector('[data-mapbox-standard-trial]');
    if (!root) return;
    try {
        await createMapboxStandardTrial(root);
    } catch (e) {
        console.error('Mapbox Standard trial failed', e);
        const status = root.querySelector('[data-mbx-status]');
        if (status) status.textContent = 'Mapbox Standard trial failed to start';
        root.dataset.metricsJson = JSON.stringify({
            blocked: true,
            reason: String(e?.message || e),
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { createMapboxStandardTrial };
