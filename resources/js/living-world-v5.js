/**
 * Living World V5 preview entry — geographic cinematic MapLibre feasibility.
 */
import { createLivingWorldV5 } from './living-map/create-living-world-v5.js';

async function boot() {
    const root = document.querySelector('[data-living-world-v5]');
    if (!root) return;
    try {
        await createLivingWorldV5(root);
    } catch (e) {
        console.error('Living World V5 boot failed', e);
        const status = root.querySelector('[data-lw-status]');
        if (status) status.textContent = 'Living World V5 failed to start';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { createLivingWorldV5 };
