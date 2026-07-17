import { defineConfig } from 'vitest/config'
import { resolve } from 'path'

const stub = (name: string): string => resolve(__dirname, `tests/stubs/${name}.ts`)

export default defineConfig({
  test: {
    environment: 'happy-dom',
    include: ['tests/**/*.test.ts'],
    setupFiles: ['tests/setup.ts'],
  },
  resolve: {
    alias: [
      { find: '@typo3/backend/modal.js', replacement: stub('modal') },
      { find: '@typo3/backend/enum/severity.js', replacement: stub('severity-enum') },
      { find: '@typo3/backend/severity.js', replacement: stub('severity') },
      { find: '@typo3/core/ajax/ajax-request.js', replacement: stub('ajax-request') },
      { find: '@typo3/backend/ajax-data-handler.js', replacement: stub('ajax-data-handler') },
      { find: '@typo3/backend/context-menu-actions.js', replacement: stub('context-menu-actions') },
    ],
  },
})
