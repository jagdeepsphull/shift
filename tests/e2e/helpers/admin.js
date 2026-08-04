// @ts-check
/**
 * Shared helpers for driving the admin back office.
 */
const { expect } = require('@playwright/test');
const { readCaptchaCode } = require('./session');

const ADMIN = {
  user: process.env.ADMIN_USER || 'e2e.admin@example.com',
  pass: process.env.ADMIN_PASS || 'E2eTest@12345',
};

/**
 * Open /sadmin/login and wait for the verification image to be issued, so the
 * session holds a code we can read.
 *
 * @param {import('@playwright/test').Page} page
 */
async function openLogin(page) {
  const captchaLoaded = page.waitForResponse(
    (r) => r.url().includes('/front/test_cap') && r.status() === 200,
  );
  await page.goto('sadmin/login');
  await captchaLoaded;
}

/**
 * Fill and submit the admin login form.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{user?: string, pass?: string, captcha?: string}} [options]
 */
async function submitLogin(page, options = {}) {
  const code = options.captcha ?? (await readCaptchaCode(page.context()));
  expect(code, 'verification code should be in the session').toBeTruthy();

  await page.fill('input[name="username"]', options.user ?? ADMIN.user);
  await page.fill('input[name="password"]', options.pass ?? ADMIN.pass);
  await page.fill('input[name="captcha"]', String(code));

  await Promise.all([
    page.waitForLoadState('load'),
    page.click('button[name="loginSubmit"]'),
  ]);
}

/**
 * Log in and land on the dashboard.
 *
 * Waits for the page to go quiet before returning: every admin page fires an
 * `ajax_getcitylist` request from its footer, and CodeIgniter ages flash
 * messages once per request - a stray in-flight request landing between a
 * redirect and the page that displays the message would swallow it.
 *
 * @param {import('@playwright/test').Page} page
 */
async function loginAsAdmin(page) {
  await openLogin(page);
  await submitLogin(page);
  await expect(page).toHaveURL(/\/sadmin\/dashboard$/);
  await settle(page);
}

/**
 * Wait until the page has no in-flight requests left.
 *
 * @param {import('@playwright/test').Page} page
 */
async function settle(page) {
  await page.waitForLoadState('networkidle').catch(() => {
    /* a still-open long poll should not fail the test */
  });
}

/**
 * Assert the response was a normal page, not a CodeIgniter error screen.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectNoServerError(page) {
  const body = await page.content();
  expect(body).not.toContain('Whoops!');
  expect(body).not.toContain('ErrorException');
  expect(body).not.toMatch(/Fatal error|Uncaught \w+Exception/);
}

/**
 * Type into a DataTables search box so a row on a later page becomes visible.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} term
 */
async function filterTable(page, term) {
  const search = page.locator('#example1_filter input');
  await expect(search).toBeVisible();
  await search.fill(term);
}

module.exports = {
  ADMIN,
  openLogin,
  submitLogin,
  loginAsAdmin,
  settle,
  expectNoServerError,
  filterTable,
};
