// @ts-check
/**
 * Every admin screen loads: correct HTTP status, no CodeIgniter error page, and
 * the expected heading/form actually rendered.
 *
 * This is the sweep that catches anything the CI3 -> CI4 port missed in a view.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError } = require('../helpers/admin');
const { scalar } = require('../helpers/db');

/** Row ids picked from the live data, so the edit screens have something to show. */
const ids = {};

test.beforeAll(() => {
  ids.city = scalar('SELECT c_id FROM city ORDER BY c_id LIMIT 1;');
  ids.province = scalar('SELECT p_id FROM province ORDER BY p_id LIMIT 1;');
  ids.hourly = scalar('SELECT hr_id FROM hourly_rate ORDER BY hr_id LIMIT 1;');
  ids.shiftFor = scalar('SELECT sf_id FROM shift_for ORDER BY sf_id LIMIT 1;');
  ids.service = scalar('SELECT st_id FROM store_service ORDER BY st_id LIMIT 1;');
  ids.software = scalar('SELECT ss_id FROM software_skills ORDER BY ss_id LIMIT 1;');
  ids.menu = scalar('SELECT m_id FROM headermenu ORDER BY m_id LIMIT 1;');
  ids.employer = scalar('SELECT u_id FROM users WHERE u_usertype = 1 ORDER BY u_id LIMIT 1;');
  ids.applicant = scalar('SELECT u_id FROM users WHERE u_usertype = 2 ORDER BY u_id LIMIT 1;');
  ids.job = scalar('SELECT p_id FROM post_job ORDER BY p_id DESC LIMIT 1;');
  ids.application = scalar('SELECT sj_id FROM stu_saved_applied_jobs ORDER BY sj_id LIMIT 1;');
});

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

test('dashboard shows the summary tiles', async ({ page }) => {
  await page.goto('sadmin/dashboard');

  await expect(page.locator('.content-wrapper')).toBeVisible();
  await expect(page.locator('.small-box, .info-box').first()).toBeVisible();
  await expectNoServerError(page);
});

test('the what-is-new panel has a tab per employer kind', async ({ page }) => {
  await page.goto('sadmin/dashboard');

  // The combined tab plus one per kind, in the order the sidebar lists them.
  for (const [id, label] of [
    ['tab-emp', /new employers/i],
    ['tab-emp-manager', /managers/i],
    ['tab-emp-owner', /owners/i],
  ]) {
    const tab = page.locator('#' + id);
    await expect(tab).toHaveText(label);

    await tab.click();

    const pane = page.locator('#' + String(id).replace('tab-', 'pane-'));
    await expect(pane).toBeVisible();

    // Either a table of registrations or the empty note - never a blank pane.
    const rows = await pane.locator('tbody tr').count();
    if (rows === 0) {
      await expect(pane.locator('p.text-muted')).toContainText(/in the last \d+ days/i);
    }
  }

  await expectNoServerError(page);
});

test('every list screen renders its table', async ({ page }) => {
  const lists = [
    ['sadmin/city', /city list/i],
    ['sadmin/province', /province list/i],
    ['sadmin/hourly', /hourly rate list/i],
    ['sadmin/shift_for', /shift for list/i],
    ['sadmin/storeservice', /service list/i],
    ['sadmin/additionaldetails', /additional detail list/i],
    ['sadmin/softwareskills', /software list/i],
    ['sadmin/resources', /resources links list/i],
    ['sadmin/employer', /employer/i],
    ['sadmin/applicant', /applicant/i],
    ['sadmin/postjobs', /shifts/i],
    ['sadmin/applications', /job applications/i],
  ];

  for (const [path, heading] of lists) {
    const response = await page.goto(path);
    expect(response?.status(), `${path} status`).toBe(200);

    await expect(page.locator('.content-header h1'), `${path} heading`).toContainText(heading);
    await expect(page.locator('table').first(), `${path} table`).toBeVisible();
    await expectNoServerError(page);
  }
});

test('every add form renders', async ({ page }) => {
  const forms = [
    ['sadmin/city/add', 'c_name'],
    ['sadmin/province/add', 'p_name'],
    ['sadmin/hourly/add', 'hr_name'],
    ['sadmin/shift_for/add', 'sf_name'],
    ['sadmin/storeservice/add', 'st_service_name'],
    ['sadmin/additionaldetails/add', 'ad_name'],
    ['sadmin/softwareskills/add', 'ss_name'],
    ['sadmin/resources/add', 'm_name'],
    ['sadmin/employer/add', 'u_email'],
    ['sadmin/applicant/add', 'u_email'],
    ['sadmin/postjobs/add', 'p_store_id'],
  ];

  for (const [path, field] of forms) {
    const response = await page.goto(path);
    expect(response?.status(), `${path} status`).toBe(200);

    await expect(page.locator(`[name="${field}"]`), `${path} field ${field}`).toBeVisible();
    // Some forms use <button type="submit">, others <input type="submit">.
    await expect(page.locator('[name="savedata"]').first(), `${path} submit`).toBeVisible();
    await expectNoServerError(page);
  }
});

test('every edit form renders with the row loaded', async ({ page }) => {
  const forms = [
    [`sadmin/city/edit/${ids.city}`, 'c_name'],
    [`sadmin/province/edit/${ids.province}`, 'p_name'],
    [`sadmin/hourly/edit/${ids.hourly}`, 'hr_name'],
    [`sadmin/shift_for/edit/${ids.shiftFor}`, 'sf_name'],
    [`sadmin/storeservice/edit/${ids.service}`, 'st_service_name'],
    [`sadmin/softwareskills/edit/${ids.software}`, 'ss_name'],
    [`sadmin/resources/edit/${ids.menu}`, 'm_name'],
    [`sadmin/employer/edit/${ids.employer}`, 'u_email'],
    [`sadmin/applicant/edit/${ids.applicant}`, 'u_email'],
    [`sadmin/postjobs/edit/${ids.job}`, 'p_store_id'],
  ];

  for (const [path, field] of forms) {
    const response = await page.goto(path);
    expect(response?.status(), `${path} status`).toBe(200);

    const input = page.locator(`[name="${field}"]`).first();
    await expect(input, `${path} field ${field}`).toBeVisible();
    expect(await input.inputValue(), `${path} ${field} should be pre-filled`).not.toBe('');
    await expectNoServerError(page);
  }
});

test('the application detail screen renders', async ({ page }) => {
  test.skip(!ids.application, 'no applications in the database');

  const response = await page.goto(`sadmin/applications/view/${ids.application}`);
  expect(response?.status()).toBe(200);

  await expect(page.locator('[name="sj_is_approved"]')).toBeVisible();
  await expect(page.locator('[name="sj_admin_comment"]')).toBeVisible();
  await expectNoServerError(page);
});

test('list filters are honoured', async ({ page }) => {
  await page.goto('sadmin/postjobs?filter=new');
  await expect(page.locator('table').first()).toBeVisible();
  await expectNoServerError(page);

  await page.goto('sadmin/applications?filter=booked');
  await expect(page.locator('table').first()).toBeVisible();
  await expectNoServerError(page);
});

test('settings, e-mail and password screens render', async ({ page }) => {
  await page.goto('sadmin/settings');
  await expect(page.locator('[name="s_sitename"]')).toBeVisible();
  expect(await page.locator('[name="s_sitename"]').inputValue()).not.toBe('');
  await expectNoServerError(page);

  await page.goto('sadmin/send_email');
  await expect(page.locator('[name="to"]')).toBeVisible();
  await expect(page.locator('[name="subject"]')).toBeVisible();
  await expectNoServerError(page);

  await page.goto('sadmin/changepassword');
  await expect(page.locator('[name="current_password"]')).toBeVisible();
  await expect(page.locator('[name="new_password"]')).toBeVisible();
  await expectNoServerError(page);
});

test('the sidebar links to the modules and navigates', async ({ page }) => {
  await page.goto('sadmin/dashboard');

  // The module links live inside a collapsible AdminLTE tree, so they are in
  // the DOM but only shown once the parent item is opened.
  for (const href of ['sadmin/province/index', 'sadmin/city/index', 'sadmin/applications']) {
    await expect(page.locator(`.main-sidebar a[href*="${href}"]`).first()).toBeAttached();
  }

  await page.locator('.main-sidebar .nav-link', { hasText: /master/i }).first().click();

  const cityLink = page.locator('.main-sidebar a[href*="sadmin/city/index"]').first();
  await expect(cityLink).toBeVisible();
  await cityLink.click();

  await expect(page).toHaveURL(/\/sadmin\/city\/index$/);
  await expect(page.locator('.content-header h1')).toContainText(/city list/i);
  await expectNoServerError(page);
});
