// @ts-check
/**
 * The full add -> validate -> edit -> toggle status -> delete lifecycle, driven
 * through the UI on the City module. Every admin CRUD screen shares the same
 * controller shape, so this covers the pattern the whole back office uses.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const CITY = 'E2E Playwright City';
const CITY_RENAMED = 'E2E Playwright City Renamed';

test.beforeEach(async ({ page }) => {
  query("DELETE FROM city WHERE c_name LIKE 'E2E %';");
  await loginAsAdmin(page);
});

test.afterAll(() => {
  query("DELETE FROM city WHERE c_name LIKE 'E2E %';");
});

test('a new city can be created from the list screen', async ({ page }) => {
  await page.goto('sadmin/city');
  await page.click('a.btn-primary:has-text("Add city")');
  await expect(page).toHaveURL(/\/sadmin\/city\/add$/);

  await page.selectOption('select[name="c_province"]', { index: 1 });
  await page.fill('input[name="c_name"]', CITY);
  await page.click('button[name="savedata"]');

  await expect(page).toHaveURL(/\/sadmin\/city$/);
  await expect(page.locator('.alert')).toContainText(/inserted successfully/i);

  expect(count('city', `c_name = '${CITY}'`), 'row should exist').toBe(1);

  // And it is visible in the list (the table is paginated, so search for it).
  await filterTable(page, CITY);
  await expect(page.locator('#example1 tbody tr', { hasText: CITY })).toHaveCount(1);
  await expectNoServerError(page);
});

test('a duplicate name is rejected by the unique rule', async ({ page }) => {
  query(
    `INSERT INTO city (c_name, c_province, c_status) VALUES ('${CITY}', ` +
      `(SELECT p_id FROM province ORDER BY p_id LIMIT 1), 1);`,
  );

  await page.goto('sadmin/city/add');
  await page.selectOption('select[name="c_province"]', { index: 1 });
  await page.fill('input[name="c_name"]', CITY);
  await page.click('button[name="savedata"]');

  await expect(page.locator('.alert-danger')).toContainText(/already taken/i);
  expect(count('city', `c_name = '${CITY}'`), 'no second row').toBe(1);
  await expectNoServerError(page);
});

test('an empty name is rejected by the server, not just the browser', async ({ page }) => {
  await page.goto('sadmin/city/add');

  // The inputs carry the HTML `required` attribute; drop client-side validation
  // so the request actually reaches the controller.
  await page.evaluate(() => {
    document.querySelectorAll('form').forEach((f) => f.setAttribute('novalidate', 'novalidate'));
  });

  await page.click('button[name="savedata"]');

  await expect(page.locator('.alert-danger')).toContainText(/field is required/i);
  expect(count('city', "c_name = ''"), 'nothing inserted').toBe(0);
  await expectNoServerError(page);
});

test('a city can be renamed, toggled and deleted', async ({ page }) => {
  query(
    `INSERT INTO city (c_name, c_province, c_status) VALUES ('${CITY}', ` +
      `(SELECT p_id FROM province ORDER BY p_id LIMIT 1), 1);`,
  );
  const id = scalar(`SELECT c_id FROM city WHERE c_name = '${CITY}';`);

  // --- edit ---
  await page.goto(`sadmin/city/edit/${id}`);
  await expect(page.locator('input[name="c_name"]')).toHaveValue(CITY);

  await page.fill('input[name="c_name"]', CITY_RENAMED);
  await page.click('button[name="updatedata"]');

  await expect(page).toHaveURL(/\/sadmin\/city$/);
  await expect(page.locator('.alert')).toContainText(/updated successfully/i);
  expect(scalar(`SELECT c_name FROM city WHERE c_id = ${id};`)).toBe(CITY_RENAMED);

  // --- change status ---
  expect(scalar(`SELECT c_status FROM city WHERE c_id = ${id};`)).toBe('1');

  await filterTable(page, CITY_RENAMED);
  await page.locator('#example1 tbody tr', { hasText: CITY_RENAMED })
    .locator('a:has-text("Change Status")')
    .click();

  await expect(page).toHaveURL(/\/sadmin\/city$/);
  expect(scalar(`SELECT c_status FROM city WHERE c_id = ${id};`), 'now inactive').toBe('0');

  // --- delete (the link asks for confirmation) ---
  await settle(page);
  page.once('dialog', (dialog) => dialog.accept());

  await filterTable(page, CITY_RENAMED);
  await page.locator('#example1 tbody tr', { hasText: CITY_RENAMED })
    .locator('a:has-text("Delete")')
    .click();

  await expect(page).toHaveURL(/\/sadmin\/city$/);
  await expect(page.locator('.alert')).toContainText(/has been deleted/i);
  expect(count('city', `c_id = ${id}`), 'row is gone').toBe(0);
  await expectNoServerError(page);
});

test('a city that is in use cannot be deleted', async ({ page }) => {
  // Pick a city an employer or a shift still points at.
  const inUse = scalar(
    'SELECT c.c_id FROM city c JOIN users u ON u.u_city = c.c_id ORDER BY c.c_id LIMIT 1;',
  );
  test.skip(!inUse, 'no city is referenced by a user');

  const before = count('city', `c_id = ${inUse}`);

  await settle(page);
  page.once('dialog', (dialog) => dialog.accept());
  await page.goto(`sadmin/city/delete/${inUse}`);

  await expect(page.locator('.alert-warning')).toContainText(/cannot delete/i);
  expect(count('city', `c_id = ${inUse}`), 'still there').toBe(before);
  await expectNoServerError(page);
});
