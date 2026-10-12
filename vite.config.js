import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/modern-ui.css',
                'resources/js/modern-ui.js',
                'resources/css/app.css',
                'resources/css/dashboard.css',
                'resources/css/user_management.css',
                'resources/css/emergency.css',
                'resources/css/incidents.css',
                'resources/css/resident_record.css',
                'resources/css/resident/dashboard.css',
                // 'resources/css/resident/profile.css', (unused; dashboard.css covers everything)
                // community alerts page styles
                'resources/css/resident/comalerts.css',

                'resources/js/app.js',
                'resources/js/resident_record.js',
                'resources/js/resident/resident.js',
                'resources/js/resident/profile.js',
                // community alerts page scripts
                'resources/js/resident/comalerts.js',
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
