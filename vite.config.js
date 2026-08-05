import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/ai.css',
                'resources/js/ai.js',
                'resources/css/crm.css',
                'resources/js/crm.js',
                'resources/css/onboarding.css',
                'resources/css/modules.css',
                'resources/js/modules.js',
                'resources/css/branding.css',
                'resources/js/branding.js',
                'resources/css/system.css',
                'resources/css/admin.css',
                'resources/css/site.css',
                'resources/js/site.js',
                'resources/css/auth.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
