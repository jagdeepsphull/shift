// @ts-check
/**
 * Stores in the back office: sub-outlets under a chain, and where each one is.
 *
 * Employers have managed their own locations since multi-store shipped, but an
 * administrator had none of it - adding a branch to a chain, or correcting an
 * address, meant opening the database. These screens are the employer's own
 * store form with an owner picker in front of it.
 *
 * They also carry the two fields a postal address does not supply: what to call
 * the spot, and the Google Maps link pasted from Share > Copy link. A pharmacy
 * inside a supermarket is not findable from its street address alone.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError, settle } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';

const CHAIN = 'e2e.storeadm.chain@example.com';
const SINGLE = 'e2e.storeadm.single@example.com';

const MAP_URL = 'https://maps.app.goo.gl/e2eExampleLink';

/** @type {Record<string, number>} */
const ids = {};
let PROVINCE = '0';
let CITY = '0';

/**
 * @param {string} email
 * @param {{role: number, company: string}} opts
 * @returns {number}
 */
function seedEmployer(email, opts) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress,
       reset_token, token_expiry)
    VALUES
      (1, 0, ${opts.role}, 0, '${email}', 'Store', 'Admin', MD5('${PASSWORD}'),
       '${opts.company}', ${PROVINCE}, 'E2E-LIC', '', '', ${PROVINCE}, ${CITY},
       '4 Store Road', 'M5A 1A1', '4165550404', '${email}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${email}';`));
}

/** @param {number} userId @param {string} name @returns {number} */
function seedStore(userId, name) {
  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${userId}, '${name}', 'E2E-NO', ${PROVINCE}, ${CITY}, '4 Store Road', 'M5A 1A1', '4165550404', 1);
  `);

  return Number(scalar(`SELECT s_id FROM store WHERE s_name = '${name}';`));
}

function removeSeeded() {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-STOREADM-%';");
  query(`
    DELETE FROM store
     WHERE u_id IN (SELECT u_id FROM (SELECT u_id FROM users WHERE u_email LIKE 'e2e.storeadm.%@example.com') x)
        OR s_name LIKE 'E2E Storeadm%';
  `);
  query(`DELETE FROM users WHERE u_email LIKE 'e2e.storeadm.%@example.com';`);
}

test.beforeAll(() => {
  removeSeeded();

  PROVINCE = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;') || '0';
  CITY = scalar(`SELECT c_id FROM city WHERE c_status = 1 AND c_province = ${PROVINCE} LIMIT 1;`) || '0';

  ids.chain = seedEmployer(CHAIN, { role: 1, company: 'E2E Storeadm Chain' });
  ids.single = seedEmployer(SINGLE, { role: 2, company: 'E2E Storeadm Single' });

  ids.chainStore = seedStore(ids.chain, 'E2E Storeadm Chain Branch One');
  ids.singleStore = seedStore(ids.single, 'E2E Storeadm Single Store');
});

test.afterAll(() => {
  removeSeeded();
});

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

/** @param {import('@playwright/test').Page} page */
async function save(page) {
  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);
  await settle(page);
}

/**
 * Pick the province and wait for its cities, which arrive over ajax.
 *
 * @param {import('@playwright/test').Page} page
 */
async function chooseLocation(page) {
  const cityLoaded = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));
  await page.selectOption('select[name="s_province"]', PROVINCE);
  await cityLoaded;
  await page.selectOption('select[name="s_city"]', CITY);
}

test('the store list is reachable from the sidebar and shows every store', async ({ page }) => {
  await page.goto('sadmin/dashboard');

  const sidebar = page.locator('aside.main-sidebar');
  const link = sidebar.locator('a[href$="/sadmin/stores"]');

  // Stores sit inside the Manage Employers tree, which is collapsed away from
  // the employer screens - so the entry is in the markup but not yet on screen.
  await expect(link).toHaveCount(1);

  await sidebar.locator('.nav-link', { hasText: /manage employers/i }).first().click();
  await expect(link).toBeVisible();

  await Promise.all([page.waitForLoadState('load'), link.click()]);

  await expect(page).toHaveURL(/\/sadmin\/stores$/);
  await expectNoServerError(page);
  await expect(page.locator('h1')).toContainText('Manage Stores');

  // Both seeded employers' stores are here, because the list is unscoped.
  await expect(page.locator('#example1')).toContainText('E2E Storeadm Single Store');
});

test('the stores entry is marked as the current screen while on it', async ({ page }) => {
  await page.goto('sadmin/stores');

  const link = page.locator('aside.main-sidebar a[href$="/sadmin/stores"]');

  // The branch it lives in has to open too, or the sidebar collapses the very
  // section the administrator is standing in.
  await expect(link).toBeVisible();
  await expect(link).toHaveClass(/active/);
});

test('the list can be scoped to one employer', async ({ page }) => {
  await page.goto(`sadmin/stores?owner=${ids.chain}`);
  await expectNoServerError(page);

  await expect(page.locator('h1')).toContainText('E2E Storeadm Chain');
  await expect(page.locator('#example1 tbody')).toContainText('E2E Storeadm Chain Branch One');
  await expect(page.locator('#example1 tbody')).not.toContainText('E2E Storeadm Single Store');
});

test('a sub-outlet is added to a multi-store owner', async ({ page }) => {
  await page.goto(`sadmin/stores/add?owner=${ids.chain}`);
  await expectNoServerError(page);

  // Adding from the chain's own list starts on that chain.
  await expect(page.locator('select[name="u_id"]')).toHaveValue(String(ids.chain));

  await page.fill('input[name="s_name"]', 'E2E Storeadm Chain Branch Two');
  await page.fill('input[name="s_number"]', 'BR-002');
  await page.fill('textarea[name="s_address"]', '77 Second Branch Way');
  await chooseLocation(page);
  await page.fill('input[name="s_pincode"]', 'M5A 2B2');
  await page.fill('input[name="s_phone"]', '4165550777');
  await page.fill('input[name="s_location_label"]', 'Inside the Superstore');
  await page.fill('input[name="s_map_url"]', MAP_URL);
  await page.fill('input[name="s_website"]', 'https://branch-two.example.com');

  await save(page);

  const sid = scalar(`SELECT s_id FROM store WHERE s_name = 'E2E Storeadm Chain Branch Two';`);
  expect(sid).not.toBe('');
  expect(scalar(`SELECT u_id FROM store WHERE s_id = ${sid};`)).toBe(String(ids.chain));
  expect(scalar(`SELECT s_address FROM store WHERE s_id = ${sid};`)).toBe('77 Second Branch Way');
  expect(scalar(`SELECT s_location_label FROM store WHERE s_id = ${sid};`)).toBe('Inside the Superstore');
  expect(scalar(`SELECT s_map_url FROM store WHERE s_id = ${sid};`)).toBe(MAP_URL);
  expect(scalar(`SELECT s_website FROM store WHERE s_id = ${sid};`)).toBe('https://branch-two.example.com');

  // The chain now holds two locations, and saving came back to its own list.
  expect(count('store', `u_id = ${ids.chain}`)).toBe(2);
  await expect(page).toHaveURL(new RegExp(`owner=${ids.chain}`));
});

test('a single-store employer is refused a second location', async ({ page }) => {
  expect(count('store', `u_id = ${ids.single}`)).toBe(1);

  await page.goto('sadmin/stores/add');

  // The form lists owners only - a manager runs one of their group's stores
  // and is given it on the employer form - so this one is forced in the way a
  // hand-edited form would, to reach the controller's own refusal behind it.
  await expect(page.locator('select[name="u_id"]')).not.toContainText('E2E Storeadm Single');

  await page.locator('select[name="u_id"]').evaluate((el, id) => {
    const select = /** @type {HTMLSelectElement} */ (el);
    const option = document.createElement('option');
    option.value = String(id);
    option.textContent = 'Forced';
    select.appendChild(option);
    select.value = String(id);
  }, ids.single);

  await page.fill('input[name="s_name"]', 'E2E Storeadm Single Extra');
  await page.fill('input[name="s_number"]', 'X-1');
  await page.fill('textarea[name="s_address"]', '88 Extra Street');
  await chooseLocation(page);

  await save(page);

  await expect(page.locator('.alert-danger')).toContainText(/single location/i);
  expect(count('store', `u_id = ${ids.single}`)).toBe(1);
  expect(count('store', "s_name = 'E2E Storeadm Single Extra'")).toBe(0);
});

test('a pasted map link is normalised, and a dangerous one is dropped', async ({ page }) => {
  await page.goto(`sadmin/stores/edit/${ids.chainStore}`);
  await expectNoServerError(page);

  // Typed without a scheme, the way somebody pastes a bare domain. Stored as
  // typed it would resolve as a path on this site rather than as a link out.
  await page.locator('input[name="s_map_url"]').evaluate((el) => {
    /** @type {HTMLInputElement} */ (el).type = 'text';
  });
  await page.fill('input[name="s_map_url"]', 'maps.google.com/?q=E2E');
  await save(page);

  expect(scalar(`SELECT s_map_url FROM store WHERE s_id = ${ids.chainStore};`))
    .toBe('https://maps.google.com/?q=E2E');

  // A scheme that runs code rather than navigating is refused outright.
  await page.goto(`sadmin/stores/edit/${ids.chainStore}`);
  await page.locator('input[name="s_map_url"]').evaluate((el) => {
    /** @type {HTMLInputElement} */ (el).type = 'text';
  });
  await page.fill('input[name="s_map_url"]', 'javascript:alert(1)');
  await save(page);

  expect(scalar(`SELECT s_map_url FROM store WHERE s_id = ${ids.chainStore};`)).toBe('');
});

test('a store with no pasted link still offers a map search for its address', async ({ page }) => {
  // The previous test cleared the link on this store, which is the state most
  // existing stores are in: an address and nothing else.
  query(`UPDATE store SET s_map_url = '' WHERE s_id = ${ids.chainStore};`);

  await page.goto(`sadmin/stores?owner=${ids.chain}`);

  const row = page.locator('#example1 tbody tr', { hasText: 'E2E Storeadm Chain Branch One' });
  const link = row.locator('a[href*="google.com/maps"]');

  await expect(link).toHaveText(/Map search/);
  await expect(link).toHaveAttribute('href', /4%20Store%20Road/);
});

test('a pasted link is preferred over the address search, and labelled as the pin', async ({ page }) => {
  query(`UPDATE store SET s_map_url = '${MAP_URL}' WHERE s_id = ${ids.chainStore};`);

  await page.goto(`sadmin/stores?owner=${ids.chain}`);

  const row = page.locator('#example1 tbody tr', { hasText: 'E2E Storeadm Chain Branch One' });
  const link = row.locator('a[href="' + MAP_URL + '"]');

  await expect(link).toHaveText(/Map pin/);
});

test('a store that shifts point at cannot be deleted, only deactivated', async ({ page }) => {
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  query(`
    INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
       p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES
      (${ids.chain}, ${ids.chainStore}, 'E2E Storeadm Chain', 'E2E-STOREADM-1', 0,
       ${PROVINCE}, ${CITY}, ${shiftFor}, 30, 30, '01-09-2027', '2027-09-01', '09:00 - 17:00',
       '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());
  `);

  await page.goto(`sadmin/stores/delete/${ids.chainStore}?owner=${ids.chain}`);
  await settle(page);

  await expect(page.locator('.alert-danger')).toContainText(/cannot be deleted/i);
  expect(count('store', `s_id = ${ids.chainStore}`)).toBe(1);

  // Deactivating is the offered alternative and does work.
  await page.goto(`sadmin/stores/changestatus/${ids.chainStore}?owner=${ids.chain}`);
  await settle(page);
  expect(scalar(`SELECT s_status FROM store WHERE s_id = ${ids.chainStore};`)).toBe('0');

  await page.goto(`sadmin/stores/changestatus/${ids.chainStore}?owner=${ids.chain}`);
  await settle(page);
  expect(scalar(`SELECT s_status FROM store WHERE s_id = ${ids.chainStore};`)).toBe('1');
});

test('a store with no shifts against it is deleted', async ({ page }) => {
  const sid = seedStore(ids.chain, 'E2E Storeadm Disposable');

  await page.goto(`sadmin/stores/delete/${sid}?owner=${ids.chain}`);
  await settle(page);

  await expect(page.locator('.alert-success')).toContainText(/deleted/i);
  expect(count('store', `s_id = ${sid}`)).toBe(0);
});

test('an employer edit page links to that employer\'s stores', async ({ page }) => {
  await page.goto(`sadmin/employer/edit/${ids.chain}`);
  await expectNoServerError(page);

  const link = page.locator(`a[href$="/sadmin/stores?owner=${ids.chain}"]`);
  await expect(link).toBeVisible();

  await Promise.all([page.waitForLoadState('load'), link.click()]);
  await expect(page.locator('h1')).toContainText('E2E Storeadm Chain');
});
