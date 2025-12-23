import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), vueDevTools(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  
  server: {
    port: 5173,
    proxy: {
      '/storage': {
        target: 'http://localhost:8000', // Aponta para o Docker API
        changeOrigin: true,
        // Opcional: rewrite não é necessário se a pasta no servidor for mesmo /storage
      }
    }
  }
})
