// @ts-check
/**
 * Admin authentication: the login form, its three failure modes, and the guard
 * that keeps signed-out visitors out of the back office.
 */
const { test, expect } = require('@playwright/test');
const { ADMIN, openLogin, submitLogin, expectNoServerError } = require('../helpers/admin');

test.describe('admin authentication', () => {
  test('login page renders with its verification image', async ({ page }) => {
    await openLogin(page);

    await expect(page.locator('h2')).toHaveText(/login account/i);
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="captcha"]')).toBeVisible();

    const captcha = page.locator('img[src*="front/test_cap"]');
    await expect(captcha).toBeVisible();
    // A blank/broken image would have zero natural width.
    expect(await captcha.evaluate((img) => /** @type {HTMLImageElement} */ (img).naturalWidth))
      .toBeGreaterThan(0);

    await expectNoServerError(page);
  });

  test('signed-out visitors are redirected to the login page', async ({ page }) => {
    for (const path of ['sadmin', 'sadmin/dashboard', 'sadmin/city', 'sadmin/settings']) {
      await page.goto(path);
      await expect(page).toHaveURL(/\/sadmin\/login$/);
    }
  });

  test('a wrong password is rejected', async ({ page }) => {
    await openLogin(page);
    await submitLogin(page, { pass: 'definitely-not-the-password' });

    await expect(page).toHaveURL(/\/sadmin\/login$/);
    await expect(page.locator('.alert-danger')).toContainText(/wrong username or password/i);
  });

  test('a wrong verification code is rejected', async ({ page }) => {
    await openLogin(page);
    await submitLogin(page, { captcha: '000000' });

    await expect(page).toHaveURL(/\/sadmin\/login$/);
    await expect(page.locator('.alert-danger')).toContainText(/invalid captcha/i);
  });

  test('a non-admin account cannot use the admin login', async ({ page }) => {
    await openLogin(page);
    // Applicants/employers sign in through the public form; sadmin/login only
    // matches u_usertype = 0.
    await submitLogin(page, { user: 'ci4test.applicant@example.com', pass: ADMIN.pass });

    await expect(page).toHaveURL(/\/sadmin\/login$/);
    await expect(page.locator('.alert-danger')).toBeVisible();
  });

  test('valid credentials reach the dashboard, and logout ends the session', async ({ page }) => {
    await openLogin(page);
    await submitLogin(page);

    await expect(page).toHaveURL(/\/sadmin\/dashboard$/);
    await expect(page.locator('.main-sidebar')).toBeVisible();
    await expectNoServerError(page);

    await page.click('a[href$="/sadmin/logout"]');
    await expect(page).toHaveURL(/\/sadmin\/login$/);

    // The session really is gone.
    await page.goto('sadmin/dashboard');
    await expect(page).toHaveURL(/\/sadmin\/login$/);
  });
});
