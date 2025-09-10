// import path from 'path';
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
// import tailwindConfig from "./tailwind.config.ts";
import tailwind from "@tailwindcss/vite";


export default defineConfig({
  plugins: [react(),tailwind()],
  build: {
    outDir: '../public/dist',  // Laravelのpublic直下にビルド成果物を出力
    emptyOutDir: true,
  },
  server: {
    host: true,   // Docker から受ける
    port: 5176,        // ← docker-compose と一致させる
    proxy:{
        '/api':{
            target:'http://localhost:8000',
            changeOrigin:true,
            secure:false,
            configure(proxy) {
                proxy.on('error', (err) => console.error('[vite-proxy]', err))
                proxy.on('proxyReq', (_, req) => console.log('[vite-proxy] ->', req.url))
                proxy.on('proxyRes', (_, req) => console.log('[vite-proxy] <-', req.url))
            },
        }
    },
  },
    resolve: {
        alias: { "@": "/src" }, // shadcn が要求する import alias
    },
})
