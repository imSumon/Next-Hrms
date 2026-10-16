import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': { target: 'http://localhost:8000', changeOrigin: false },
      '/login': { target: 'http://localhost:8000', changeOrigin: false },
      '/company': { target: 'http://localhost:8000', changeOrigin: false },
      '/logout': { target: 'http://localhost:8000', changeOrigin: false },
      '/employees': { target: 'http://localhost:8000', changeOrigin: false },
      '/css': { target: 'http://localhost:8000', changeOrigin: false },
    },
  },
});
