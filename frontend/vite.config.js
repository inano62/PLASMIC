import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [react()],
    server: {
        port: 5176,
        strictPort: true,
        host: 'localhost',
        proxy: {
            '/api':     { target: 'http://localhost:8000', changeOrigin: true },
            '/sanctum': { target: 'http://localhost:8000', changeOrigin: true },
            '/login':   { target: 'http://localhost:8000', changeOrigin: true },
            '/logout':  { target: 'http://localhost:8000', changeOrigin: true },
        },
    },
    build: {
        outDir: '../public/dist',
        emptyOutDir: true,
    },
    resolve: {
        alias: {
            '@': '/src',
        },
    },
})
