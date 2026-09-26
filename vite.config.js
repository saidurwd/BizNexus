import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'node_modules/admin-lte/dist/css/adminlte.min.css',
                'node_modules/admin-lte/dist/js/adminlte.min.js',
                'resources/js/erp.js',
            ],
            refresh: true,
        }),
    ],
});
