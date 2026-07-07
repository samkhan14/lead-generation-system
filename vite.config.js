import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@materio-scss': path.resolve(__dirname, 'resources/scss/materio'),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                loadPaths: [
                    path.resolve(__dirname, 'resources/scss/materio'),
                    path.resolve(__dirname, 'node_modules'),
                ],
                quietDeps: true,
            },
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
});
