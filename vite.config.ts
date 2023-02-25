import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import devManifest from 'vite-plugin-dev-manifest';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    devManifest()
  ],
  publicDir: false,
  base: '/build/',
  build: {
    manifest: true,
    outDir: 'public/build/',
    rollupOptions: {
      input: {
        admin: './themes/admin/src/js/app.ts',
        app: './themes/default/src/js/app.ts',
      },
    },
  },
})
