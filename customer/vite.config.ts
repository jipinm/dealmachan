import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig(({ mode }) => {
  // Load .env / .env.[mode] so VITE_* vars are available inside the config
  const env = loadEnv(mode, process.cwd(), '')
  const apiOrigin = env.VITE_API_ORIGIN

  if (!apiOrigin) {
    throw new Error(
      '[vite.config] VITE_API_ORIGIN is not set.\n' +
      'Add VITE_API_ORIGIN=http://<your-api-host> to your .env file.',
    )
  }

  return {
    plugins: [react()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
    server: {
      port: 5174,
      proxy: {
        // Proxy all /api/* calls to the PHP backend during development
        // Target is read from VITE_API_ORIGIN in .env (required — no trailing slash)
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
            vendor: ['react', 'react-dom', 'react-router-dom'],
            query:  ['@tanstack/react-query'],
            motion: ['framer-motion'],
          },
        },
      },
    },
  }
})
