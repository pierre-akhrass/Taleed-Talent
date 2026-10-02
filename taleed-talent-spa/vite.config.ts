import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
export default defineConfig({
  plugins: [react()],
  base: process.env.VITE_APP_BASE ?? './',
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    allowedHosts: ['talent.taleed.test'],
    hmr: {
      host: 'talent.taleed.test',
      protocol: 'wss',
      port: 9443,
    },
  },
  test: { environment: 'jsdom', globals: true, setupFiles: ['./tests/setup.ts'], include: ['tests/**/*.test.ts', 'tests/**/*.test.tsx'] },
  build: { outDir: process.env.VITE_BACKEND_BUILD ? '../backend/public/app' : 'dist', emptyOutDir: true, sourcemap: false, chunkSizeWarningLimit: 850 },
});
