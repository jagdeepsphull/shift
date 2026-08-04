// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright configuration for the PickAShift admin end-to-end tests.
 *
 * The tests run against the app as served by WAMP/Apache - they do NOT start a
 * server themselves, because the application needs mod_rewrite and the real
 * MySQL database. Start WAMP first, then `npm test` in this folder.
 *
 * Override the target with:  BASE_URL=http://localhost/pickashift npm test
 */
module.exports = defineConfig({
  testDir: './specs',
  timeout: 60_000,
  expect: { timeout: 10_000 },

  // The tests share one admin account and touch the same rows, so keep them
  // serial rather than racing each other against a single database.
  fullyParallel: false,
  workers: 1,

  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,

  reporter: [['list'], ['html', { open: 'never' }]],

  globalSetup: require.resolve('./helpers/global-setup'),
  globalTeardown: require.resolve('./helpers/global-teardown'),

  use: {
    // The trailing slash matters: the app lives in a sub-folder, and URL
    // resolution would drop it otherwise. Specs use relative paths
    // ("sadmin/login", never "/sadmin/login") for the same reason.
    baseURL: (process.env.BASE_URL || 'http://localhost/pickashift').replace(/\/?$/, '/'),
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'off',
    ignoreHTTPSErrors: true,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
