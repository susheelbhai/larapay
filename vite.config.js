import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
  root: path.resolve(__dirname, 'resources/js'),
  plugins: [
    laravel({
      input: ['resources/js/app.tsx'],  // your React entrypoint here
      refresh: false, // disable refresh if used standalone
    }),
  ],
  resolve: {
    alias: {
      '@larapay': path.resolve(__dirname, 'resources/js'),
    },
  },
});
