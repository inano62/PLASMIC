import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

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
        strictPort: true,
        host: 'localhost',
        proxy: {
            // フロントからは /api/... で呼ぶこと！
            '/api':     { target: 'http://localhost:8000', changeOrigin: true },
            '/sanctum': { target: 'http://localhost:8000', changeOrigin: true },
            '/login':   { target: 'http://localhost:8000', changeOrigin: true },
            '/logout':  { target: 'http://localhost:8000', changeOrigin: true },
        },
    },
    build: { outDir: '../public/dist', emptyOutDir: true },
    resolve: { alias: { '@': '/src' } },
});
