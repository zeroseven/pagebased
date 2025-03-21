import { resolve } from 'path'
import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    inlineDynamicImports: true,
    manifest: false,
    emptyOutDir: false,
    rollupOptions: {
      preserveEntrySignatures: 'strict',
      input: {
        'Pagination': resolve(__dirname, 'Resources/Private/JavaScript/Pagination.ts'),
        'Backend/TagsElement': resolve(__dirname, 'Resources/Private/JavaScript/Backend/TagsElement.ts'),
        'Backend/TagsElementStyles': resolve(__dirname, 'Resources/Private/Scss/Backend/TagsElement.scss')
      },
      output: {
        entryFileNames: (assetInfo) => {
          return assetInfo.name.endsWith('.scss') ? '' : `JavaScript/[name].js`;
        },
        chunkFileNames: `JavaScript/[name].js`,
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'Css/Backend/[name].css'
          }
          return `[name].[ext]`
        }
      }
    },
    target: 'modules',
    minify: 'esbuild',
    outDir: resolve(__dirname, 'Resources/Public')
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
