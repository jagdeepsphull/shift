// @ts-check
/**
 * The Additional Details master: the same add -> validate -> edit -> toggle ->
 * delete lifecycle the City module is covered for, on the newest list.
 *
 * It is worth its own spec rather than a row in the `admin-pages` sweep because
 * the table starts empty, so there is no live row for the edit screen to load -
 * the tests here create what they need and clean up after themselves.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const DETAIL = 'E2E Playwright Detail';
const DETAIL_RENAMED = 'E2E Playwright Detail Renamed';

test.beforeEach(async ({ page }) => {
  query("DELETE FROM additional_details WHERE ad_name LIKE 'E2E %';");
  await loginAsAdmin(page);
});

test.afterAll(() => {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-DETAIL-%';");
  query("DELETE FROM additional_details WHERE ad_name LIKE 'E2E %';");
});

test('the list is reachable from the sidebar', async ({ page }) => {
  await page.click('.nav-sidebar a:has-text("Main Master")');
  await page.click('.nav-sidebar a:has-text("Additional Details")');

  await expect(page).toHaveURL(/\/sadmin\/additionaldetails\/index$/);
  await expect(page.locator('.content-header h1')).toContainText(/additional detail list/i);
  await expectNoServerError(page);
});

test('a new additional detail can be created from the list screen', async ({ page }) => {
  await page.goto('sadmin/additionaldetails');
  await page.click('a.btn-primary:has-text("Add Additional Detail")');
  await expect(page).toHaveURL(/\/sadmin\/additionaldetails\/add$/);

  await page.fill('input[name="ad_name"]', DETAIL);
  await page.click('button[name="savedata"]');

  await expect(page).toHaveURL(/\/sadmin\/additionaldetails$/);
  await expect(page.locator('.alert')).toContainText(/inserted successfully/i);

  expect(count('additional_details', `ad_name = '${DETAIL}'`), 'row should exist').toBe(1);

  // The add form posts a name and nothing else, so the column default decides
  // the status - it has to land Active, not needing a second click to be used.
  expect(scalar(`SELECT ad_status FROM additional_details WHERE ad_name = '${DETAIL}';`)).toBe('1');

  await filterTable(page, DETAIL);
  await expect(page.locator('#example1 tbody tr', { hasText: DETAIL })).toHaveCount(1);
  await expectNoServerError(page);
});

test('a duplicate name is rejected by the unique rule', async ({ page }) => {
  query(`INSERT INTO additional_details (ad_name, ad_status) VALUES ('${DETAIL}', 1);`);

  await page.goto('sadmin/additionaldetails/add');
  await page.fill('input[name="ad_name"]', DETAIL);
  await page.click('button[name="savedata"]');

  await expect(page.locator('.alert-danger')).toContainText(/already taken/i);
  expect(count('additional_details', `ad_name = '${DETAIL}'`), 'no second row').toBe(1);
  await expectNoServerError(page);
});

test('an empty name is rejected by the server, not just the browser', async ({ page }) => {
  await page.goto('sadmin/additionaldetails/add');

  // The input carries the HTML `required` attribute; drop client-side
  // validation so the request actually reaches the controller.
  await page.evaluate(() => {
    document.querySelectorAll('form').forEach((f) => f.setAttribute('novalidate', 'novalidate'));
  });

  await page.click('button[name="savedata"]');

  await expect(page.locator('.alert-danger')).toContainText(/field is required/i);
  expect(count('additional_details', "ad_name = ''"), 'nothing inserted').toBe(0);
  await expectNoServerError(page);
});

test('the master is offered as a tick-box group on the shift form', async ({ page }) => {
  query(`INSERT INTO additional_details (ad_name, ad_status) VALUES ('${DETAIL}', 1);`);
  query("INSERT INTO additional_details (ad_name, ad_status) VALUES ('E2E Deactive Detail', 0);");
  const id = scalar(`SELECT ad_id FROM additional_details WHERE ad_name = '${DETAIL}';`);

  await page.goto('sadmin/postjobs/add');

  const grid = page.locator('#cbg_p_additional_details');
  await expect(grid).toBeVisible();

  // Beside Software and Details, not instead of them.
  await expect(page.locator('#cbg_p_skills')).toBeVisible();
  await expect(page.locator('#cbg_p_services')).toBeVisible();

  await expect(grid.locator(`input[value="${id}"]`)).toHaveCount(1);
  await expect(grid, 'a deactivated row is not offered').not.toContainText('E2E Deactive Detail');

  // Optional, unlike the two beside it - the master can legitimately be empty.
  await expect(grid).toHaveAttribute('data-required', '0');
  await expectNoServerError(page);
});

test('a shift remembers which additional details were ticked', async ({ page }) => {
  query(`INSERT INTO additional_details (ad_name, ad_status) VALUES ('${DETAIL}', 1);`);
  const id = scalar(`SELECT ad_id FROM additional_details WHERE ad_name = '${DETAIL}';`);

  const owner = scalar('SELECT u_id FROM users WHERE u_usertype = 1 AND u_status = 1 ORDER BY u_id LIMIT 1;');
  const store = scalar(`SELECT s_id FROM store WHERE u_id = ${owner} ORDER BY s_id LIMIT 1;`);
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');
  test.skip(!owner || !store, 'no active employer with a store to hang a shift on');

  // Software and Details have to hold something: both groups are required, and
  // the form's own guard refuses to submit while either is empty - which would
  // fail this test for a reason that has nothing to do with what it checks.
  const skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 LIMIT 1;');
  const service = scalar('SELECT st_id FROM store_service WHERE st_status = 1 LIMIT 1;');

  query(`
    INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
       p_skills, p_services, p_additional_details, p_jobinfo, p_featured, p_status, p_approved,
       created, modified)
    VALUES
      (${owner}, ${store}, 'E2E Detail Co', 'E2E-DETAIL-1', 0,
       (SELECT p_id FROM province ORDER BY p_id LIMIT 1), (SELECT c_id FROM city ORDER BY c_id LIMIT 1),
       ${shiftFor}, 30, 30, '01-09-2027', '2027-09-01', '09:00 - 17:00',
       '${skill}', '${service}', '${id}', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());
  `);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-DETAIL-1';");

  // What was stored comes back ticked.
  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await expect(page.locator(`#cbg_p_additional_details input[value="${id}"]`)).toBeChecked();

  // And unticking the last box clears the column rather than leaving the old
  // value behind - nothing is posted for an all-clear group.
  await page.click(`#cbg_p_additional_details label[for="cbg_p_additional_details_${id}"]`);
  await page.click('button[name="savedata"], input[name="savedata"]');
  await settle(page);

  expect(scalar(`SELECT p_additional_details FROM post_job WHERE p_id = ${shift};`)).toBe('');

  query(`DELETE FROM post_job WHERE p_id = ${shift};`);
  await expectNoServerError(page);
});

test('an additional detail can be renamed, toggled and deleted', async ({ page }) => {
  query(`INSERT INTO additional_details (ad_name, ad_status) VALUES ('${DETAIL}', 1);`);
  const id = scalar(`SELECT ad_id FROM additional_details WHERE ad_name = '${DETAIL}';`);

  // --- edit ---
  await page.goto(`sadmin/additionaldetails/edit/${id}`);
  await expect(page.locator('input[name="ad_name"]')).toHaveValue(DETAIL);

  await page.fill('input[name="ad_name"]', DETAIL_RENAMED);
  await page.click('button[name="updatedata"]');

  await expect(page).toHaveURL(/\/sadmin\/additionaldetails$/);
  await expect(page.locator('.alert')).toContainText(/updated successfully/i);
  expect(scalar(`SELECT ad_name FROM additional_details WHERE ad_id = ${id};`)).toBe(DETAIL_RENAMED);

  // --- change status ---
  await filterTable(page, DETAIL_RENAMED);
  await page.locator('#example1 tbody tr', { hasText: DETAIL_RENAMED })
    .locator('a:has-text("Change Status")')
    .click();

  await expect(page).toHaveURL(/\/sadmin\/additionaldetails$/);
  expect(scalar(`SELECT ad_status FROM additional_details WHERE ad_id = ${id};`), 'now inactive').toBe('0');

  // --- delete (the link asks for confirmation) ---
  await settle(page);
  page.once('dialog', (dialog) => dialog.accept());

  await filterTable(page, DETAIL_RENAMED);
  await page.locator('#example1 tbody tr', { hasText: DETAIL_RENAMED })
    .locator('a:has-text("Delete")')
    .click();

  await expect(page).toHaveURL(/\/sadmin\/additionaldetails$/);
  await expect(page.locator('.alert')).toContainText(/has been deleted/i);
  expect(count('additional_details', `ad_id = ${id}`), 'row is gone').toBe(0);
  await expectNoServerError(page);
});
