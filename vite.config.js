import {resolve} from 'path'
import {defineConfig} from 'vite';

export default defineConfig({
  build: {
    inlineDynamicImports: true,
    manifest: false,
    rollupOptions: {
      preserveEntrySignatures: 'strict',
      input: {
        'Pagination': resolve(__dirname, 'Resources/Private/JavaScript/Pagination.ts'),
        'Backend/TagsElement': resolve(__dirname, 'Resources/Private/JavaScript/Backend/TagsElement.ts')
      },
      output: {
        entryFileNames: `[name].js`,
        chunkFileNames: `[name].js`,
        assetFileNames: `[name].[ext]`
      }
    },
    target: 'modules',
    minify: 'esbuild',
    outDir: resolve(__dirname, 'Resources/Public/JavaScript')
  },
  esbuild: {
    minifyIdentifiers: false
  },
  css: {
    devSourcemap: true
  },
  plugins: [],
  publicDir: false,
  workDir: './'
});
