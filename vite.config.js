import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // <--- IMPORTANTE: El plugin de Vue

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js', // Tu punto de entrada
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '127.0.0.1',
        hmr: {
            host: 'localhost',
        },
    },
});