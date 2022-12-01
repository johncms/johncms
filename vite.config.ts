import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  base: '/',
  build: {
    manifest: true,
    outDir: 'public/',
    rollupOptions: {
      input: {
        admin: './themes/admin/src/js/app.ts',
        app: './themes/default/src/js/app.ts',
      },
    },
  },
})
