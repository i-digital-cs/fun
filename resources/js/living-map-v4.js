/**
 * Living Map V4.0 preview entry — boots hybrid cinematic Discover + Navigate.
 */
import { createLivingMapV4 } from './living-map/create-living-map-v4.js';

async function boot() {
    const root = document.querySelector('[data-living-map-v4]');
    if (!root) return;
    try {
        await createLivingMapV4(root);
    } catch (e) {
        console.error('Living Map V4 boot failed', e);
        const status = root.querySelector('[data-lm-status]');
        if (status) status.textContent = 'Living Map V4 failed to start';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { createLivingMapV4 };
