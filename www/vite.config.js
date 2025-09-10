import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        port: 5176,
        host: true,
        proxy: {
            '/api':     { target: 'http://web', changeOrigin: true },
            '/sanctum': { target: 'http://web', changeOrigin: true },
            '/login':   { target: 'http://web', changeOrigin: true },
            '/logout':  { target: 'http://web', changeOrigin: true },
        },
    },
    build: { outDir: '../public/dist', emptyOutDir: true },
    resolve: { alias: { '@': '/src' } },
});
