// frontend/vite.config.ts
import { defineConfig } from 'vite'
// @ts-ignore
import react from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [react()],
    server: {
        host: true,         // = 0.0.0.0 で待つ
        port: 5176,
        strictPort: true,
        hmr: { clientPort: 5176 },
    },
})
