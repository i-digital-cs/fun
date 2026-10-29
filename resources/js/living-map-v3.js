/**
 * Living Map V3.3 entry — boots reusable createLivingMap on [data-living-map].
 * Home/public: living_map.replace_home_mini_map
 * Discover: living_map.replace_discover_map (mode=discover)
 */
import { createLivingMap } from './living-map/create-living-map.js';

async function boot() {
    const roots = document.querySelectorAll('[data-living-map]');
    for (const root of roots) {
        try {
            await createLivingMap(root);
        } catch (e) {
            console.warn('Living Map boot failed', e);
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { createLivingMap };
