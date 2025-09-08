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
            '/api': {
                target: 'http://localhost:8000', // ← Laravel (nginx) へ
                changeOrigin: true,
            },
        },
    },
});
