/**
 * Living World procedural 3D feasibility POC entry.
 */
import { createLivingWorldProcedural } from './living-map/create-living-world-procedural.js';

async function boot() {
    const root = document.querySelector('[data-living-world-procedural]');
    if (!root) return;
    try {
        await createLivingWorldProcedural(root);
    } catch (e) {
        console.error('Procedural POC failed', e);
        const status = root.querySelector('[data-lwp-status]');
        if (status) status.textContent = 'Procedural POC failed to start';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { createLivingWorldProcedural };
