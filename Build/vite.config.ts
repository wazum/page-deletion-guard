import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  build: {
    outDir: '../Resources/Public',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        'custom-delete-handler': resolve(__dirname, 'src/DeleteConfirmation/CustomDeleteHandler.ts'),
        'ajax-data-handler-interceptor': resolve(__dirname, 'src/AjaxDataHandlerInterceptor.ts')
      },
      external: [/^@typo3\//],
      output: {
        entryFileNames: 'JavaScript/[name].js',
        chunkFileNames: 'JavaScript/[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.names?.[0]?.endsWith('.css')) {
            return 'Css/[name][extname]'
          }
          return 'assets/[name][extname]'
        },
        exports: 'auto'
      },
      treeshake: {
        moduleSideEffects: true,
        propertyReadSideEffects: false
      },
      preserveEntrySignatures: 'exports-only'
    }
  }
})
