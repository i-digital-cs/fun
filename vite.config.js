import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/living-map-v35.css',
                'resources/css/living-map-v4.css',
                'resources/css/living-world-v5.css',
                'resources/css/living-world-procedural.css',
                'resources/css/mapbox-standard-trial.css',
                'resources/js/app.js',
                'resources/js/event-chat.js',
                'resources/js/dm-chat.js',
                'resources/js/chat-inbox.js',
                'resources/js/chat-v2.js',
                'resources/js/auth-access.js',
                'resources/js/home-mini-map.js',
                'resources/js/living-map-v3.js',
                'resources/js/living-map-v4.js',
                'resources/js/living-world-v5.js',
                'resources/js/living-world-procedural.js',
                'resources/js/mapbox-standard-trial.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
