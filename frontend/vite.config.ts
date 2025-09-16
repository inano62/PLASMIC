import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindVite from '@tailwindcss/vite'      // ← Viteプラグイン
import tailwindcss from 'tailwindcss'              // ← PostCSSプラグイン
import autoprefixer from 'autoprefixer'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
const __dirname = path.dirname(fileURLToPath(import.meta.url))
export default defineConfig({
    plugins: [
        react(),
        tailwindVite(),                                 // Vite 用
    ],
    build: {
        outDir: path.resolve(__dirname, '../www/public/dist'),                    // Laravelの public/dist に出力
        emptyOutDir: true,
    },
    css: {
        postcss: { plugins: [tailwindcss(), autoprefixer()] }, // PostCSS 用
    },
    server: {
        host: '0.0.0.0',
        port: 5176,
        strictPort: true,
        proxy: {
            '/api': {
                target: 'http://localhost:8000',           // ← API がホストならこのまま
                changeOrigin: true,
                secure: false,
                configure(proxy) {
                    proxy.on('error', (err) => console.error('[vite-proxy]', err))
                    proxy.on('proxyReq', (_, req) => console.log('[vite-proxy] ->', req.url))
                    proxy.on('proxyRes', (_, req) => console.log('[vite-proxy] <-', req.url))
                },
            },
            '/sanctum': { target: 'http://localhost:8000', changeOrigin: true },
        },
    },
    resolve: {
        alias: { '@': '/src' },
    },
})
