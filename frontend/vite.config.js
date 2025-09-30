import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
    base: '/',
    // base: '/dist/',
    plugins: [react()],
    server: {
        port: 5176,
        strictPort: true,
        host: 'localhost',
        proxy:  {
            "/api": "http://localhost:8000",
            // '/api':     { target: 'http://localhost:8000', changeOrigin: true, secure: false, },
            // '/sanctum': { target: 'http://localhost:8000', changeOrigin: true },
            // '/login':   { target: 'http://localhost:8000', changeOrigin: true },
            // '/logout':  { target: 'http://localhost:8000', changeOrigin: true },
            // '^/api(/|$)': { target: 'http://localhost:8000', changeOrigin: true },
            // '^/(login|logout|register)$': { target: 'http://localhost:8000', changeOrigin: true },
            // '^/sanctum/csrf-cookie$': { target: 'http://localhost:8000', changeOrigin: true },
        },
    },
    build: {
        outDir: '../www/public/dist',
        emptyOutDir: true,
    },
    resolve: {
        alias: {
            '@': '/src',
        },
    },
})
