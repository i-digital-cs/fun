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

        return;
    }

    window.Echo = null;
}
