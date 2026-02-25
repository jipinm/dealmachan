import { defineConfig, loadEnv } from 'vitest/config'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig(({ mode }) => {
  // Load .env / .env.[mode] so we can reference VITE_* vars inside the config
  const env = loadEnv(mode, process.cwd(), '')
  const apiOrigin = env.VITE_API_ORIGIN || 'http://dealmachan-api.local'

  return {
    test: {
      globals: true,
      environment: 'jsdom',
      setupFiles: ['./src/test/setup.ts'],
      css: false,
      coverage: {
        provider: 'v8',
        reporter: ['text', 'html'],
        include: ['src/**/*.{ts,tsx}'],
        exclude: ['src/test/**', 'src/main.tsx', 'src/router.tsx'],
      },
    },
    plugins: [react()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
    server: {
      port: 5173,
      proxy: {
        // Proxy /api/* to PHP backend during development
        // Target is read from VITE_API_ORIGIN in .env (falls back to http://dealmachan-api.local)
        '/api': {
          target: apiOrigin,
          changeOrigin: true,
          secure: false,
        },
      },
    },
    build: {
      outDir: 'dist',
      sourcemap: false,
      minify: 'esbuild',
      rollupOptions: {
        output: {
          manualChunks: {
            vendor:  ['react', 'react-dom', 'react-router-dom'],
            query:   ['@tanstack/react-query'],
            charts:  ['recharts'],
            motion:  ['framer-motion'],
          },
        },
      },
    },
  }
})
