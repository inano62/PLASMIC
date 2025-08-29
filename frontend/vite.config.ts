// import path from 'path';
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'


// const __filename = fileURLToPath(import.meta.url);
// const __dirname = path.dirname(__filename);
// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  build: {
    outDir: '../public/dist',  // Laravelのpublic直下にビルド成果物を出力
    emptyOutDir: true,
  },
  // resolve: {
  //   alias: {
  //     '@': path.resolve(__dirname, './src'),
  //   },
  // }
})
