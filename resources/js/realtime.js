import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export function initRealtime() {
    window.Pusher = Pusher;

    const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
    const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

    // Prefer Pusher when configured (shared hosting). Do not fall back to a
    // stale Reverb key — that leaves the browser listening on the wrong host.
    if (pusherKey) {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster || 'mt1',
            forceTLS: true,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
        });
        window.dispatchEvent(new CustomEvent('funnity:echo-ready'));
        return;
    }

    if (reverbKey) {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
        });
        window.dispatchEvent(new CustomEvent('funnity:echo-ready'));
        return;
    }

    window.Echo = null;
}

/** Run after Echo exists (app.js loads realtime asynchronously). */
export function whenEchoReady(callback, { attempts = 50, intervalMs = 100 } = {}) {
    if (typeof callback !== 'function') {
        return;
    }

    if (window.Echo) {
        callback(window.Echo);
        return;
    }

    const onReady = () => {
        if (window.Echo) {
            callback(window.Echo);
        }
    };

    window.addEventListener('funnity:echo-ready', onReady, { once: true });

    let tries = 0;
    const timer = window.setInterval(() => {
        tries += 1;
        if (window.Echo) {
            window.clearInterval(timer);
            window.removeEventListener('funnity:echo-ready', onReady);
            callback(window.Echo);
            return;
        }
        if (tries >= attempts) {
            window.clearInterval(timer);
            window.removeEventListener('funnity:echo-ready', onReady);
        }
    }, intervalMs);
}
