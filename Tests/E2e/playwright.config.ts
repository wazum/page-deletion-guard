import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: process.env.CI ? 'github' : 'list',
  outputDir: 'test-results/',

  use: {
    baseURL: process.env.TYPO3_BASE_URL || 'http://127.0.0.1:8080',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    ignoreHTTPSErrors: true,
  },

  projects: [
    // Setup projects for authentication
    {
      name: 'setup-admin',
      testMatch: /fixtures\/admin\.setup\.ts/,
    },
    {
      name: 'setup-editor-bypass',
      testMatch: /fixtures\/editor-bypass\.setup\.ts/,
    },
    {
      name: 'setup-editor-restricted',
      testMatch: /fixtures\/editor-restricted\.setup\.ts/,
    },

    // Test projects with authentication
    {
      name: 'admin',
      use: {
        ...devices['Desktop Chrome'],
        storageState: '.auth/admin.json',
      },
      dependencies: ['setup-admin'],
      testMatch: /tests\/admin-.*\.spec\.ts/,
    },
    {
      name: 'list-module',
      use: {
        ...devices['Desktop Chrome'],
      },
      dependencies: ['setup-admin', 'setup-editor-bypass', 'setup-editor-restricted'],
      testMatch: /tests\/list-module\.spec\.ts/,
    },
    {
      name: 'editor-bypass',
      use: {
        ...devices['Desktop Chrome'],
        storageState: '.auth/editor-bypass.json',
      },
      dependencies: ['setup-editor-bypass'],
      testMatch: /tests\/group-bypass\.spec\.ts/,
    },
    {
      name: 'editor-restricted',
      use: {
        ...devices['Desktop Chrome'],
        storageState: '.auth/editor-restricted.json',
      },
      dependencies: ['setup-editor-restricted'],
      testMatch: /tests\/(guard-disabled|dialog-content|editor-restricted-blocked)\.spec\.ts/,
    },
  ],
});
