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
            // '^/(api|sanctum|login|logout|register|up)': {
            //     target: 'http://localhost:8000',
            //     changeOrigin: true,
            //     secure: false,
            // },
            // '^/sanctum/csrf-cookie$': {
            //     target: 'http://localhost:8000',
            //     changeOrigin: true,
            // },
            '/api':     { target: 'http://web', changeOrigin: true },
            '/sanctum': { target: 'http://web', changeOrigin: true },
            '/login':   { target: 'http://web', changeOrigin: true },
            '/logout':  { target: 'http://web', changeOrigin: true },
            '^/api(/|$)': { target: 'http://localhost:8000', changeOrigin: true },
            '^/(login|logout|register)$': { target: 'http://localhost:8000', changeOrigin: true },
            '^/sanctum/csrf-cookie$': { target: 'http://localhost:8000', changeOrigin: true },
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
