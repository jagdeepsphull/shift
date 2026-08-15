// @ts-check
/**
 * The back-office employer form offers the same three kinds registration does,
 * and converts an existing account between them.
 *
 * Until now the form had no such choice: every employer an administrator added
 * was saved on `u_emp_role` 0 - the shape accounts had before multi-store - and
 * so belonged to no kind in the sidebar. The 62 employers that predate the
 * feature sit on that same role and have to be classified without anybody
 * opening the database, which is what the dropdown on the edit form is for.
 *
 * Two conversions would leave the data contradicting itself and are refused:
 * a multi-store owner holding several stores cannot become a single-store
 * account, and a group that managers still answer to cannot stop being a group.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError, settle } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';

const GROUP = 'e2e.kindform.group@example.com';
const LEGACY = 'e2e.kindform.legacy@example.com';
const BUSY = 'e2e.kindform.busy@example.com';
const MGROUP = 'e2e.kindform.mgroup@example.com';
const MANAGER = 'e2e.kindform.manager@example.com';

/** @type {Record<string, number>} */
const ids = {};

/**
 * A real province and one of its cities.
 *
 * The employer form asks a single-location kind for both and marks them
 * required, so a fixture left on 0 cannot be saved as one: the browser blocks
 * the submit at an empty dropdown and the controller is never reached.
 */
let PROVINCE = '0';
let CITY = '0';

/**
 * @param {string} email
 * @param {{role: number, parent: number, company: string}} opts
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
      (1, 0, ${opts.role}, ${opts.parent}, '${email}', 'Kind', 'Form', MD5('${PASSWORD}'),
       '${opts.company}', ${PROVINCE}, 'E2E-LIC', '', '', ${PROVINCE}, ${CITY},
       '9 Kind Street', 'M5A 1A1', '4165550909', '${email}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${email}';`));
}

/** @param {number} userId @param {string} name */
function seedStore(userId, name) {
  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${userId}, '${name}', 'E2E-NO', 0, 0, '9 Kind Street', 'M5A 1A1', '4165550909', 1);
  `);
}

function removeSeeded() {
  query(`
    DELETE FROM store
     WHERE u_id IN (SELECT u_id FROM (SELECT u_id FROM users WHERE u_email LIKE 'e2e.kindform.%@example.com') x);
  `);
  query(`DELETE FROM users WHERE u_email LIKE 'e2e.kindform.%@example.com';`);
}

test.beforeAll(() => {
  removeSeeded();

  PROVINCE = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;') || '0';
  CITY = scalar(`SELECT c_id FROM city WHERE c_status = 1 AND c_province = ${PROVINCE} LIMIT 1;`) || '0';

  // An approved multi-store owner, so it appears in the Corporate Group list -
  // with a location, because a manager now joins one of the group's stores
  // rather than describing one of their own.
  ids.group = seedEmployer(GROUP, { role: 1, parent: 0, company: 'E2E Kindform Group' });
  seedStore(ids.group, 'E2E Kindform Group Store');

  // One of the accounts that predates multi-store: role 0, one store, no kind.
  ids.legacy = seedEmployer(LEGACY, { role: 0, parent: 0, company: 'E2E Kindform Legacy' });
  seedStore(ids.legacy, 'E2E Kindform Legacy Store');

  // A multi-store owner that already holds three locations.
  ids.busy = seedEmployer(BUSY, { role: 1, parent: 0, company: 'E2E Kindform Busy' });
  seedStore(ids.busy, 'E2E Kindform Busy One');
  seedStore(ids.busy, 'E2E Kindform Busy Two');
  seedStore(ids.busy, 'E2E Kindform Busy Three');

  // A manager on a group of its own, with a second location to be moved to.
  // Its own, because the conversion tests below count the stores on `group`
  // and `busy` - a location added to either would change what they refuse.
  ids.mgroup = seedEmployer(MGROUP, { role: 1, parent: 0, company: 'E2E Kindform Manager Group' });
  seedStore(ids.mgroup, 'E2E Kindform Manager Store One');
  seedStore(ids.mgroup, 'E2E Kindform Manager Store Two');

  ids.manager = seedEmployer(MANAGER, { role: 2, parent: ids.mgroup, company: '' });
  query(`UPDATE users SET u_store_id =
           (SELECT s_id FROM store WHERE s_name = 'E2E Kindform Manager Store One' LIMIT 1)
         WHERE u_id = ${ids.manager};`);
});

test.afterAll(() => {
  removeSeeded();
});

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

/**
 * Choose a corporate group and one of its stores - the pair a manager is asked
 * for on both forms. The store list is filled from the group over ajax, so the
 * response has to land before there is anything to pick, and the dropdown is
 * `required` once shown: leaving it empty stops the submit in the browser and
 * the controller under test is never reached.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string|number} groupId
 * @returns {Promise<string|null>} the store that was chosen
 */
async function pickGroupStore(page, groupId) {
  const storesLoaded = page.waitForResponse((r) => r.url().includes('ajax_getstorelist'));
  await page.selectOption('select[name="u_parent_id"]', String(groupId));
  await storesLoaded;

  const store = page.locator('select[name="u_store_id"]');
  const storeId = await store.locator('option:not([value=""])').first().getAttribute('value');
  await store.selectOption(String(storeId));

  return storeId;
}

/**
 * Save the employer edit form, which needs its required fields filled.
 *
 * @param {import('@playwright/test').Page} page
 */
async function save(page) {
  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);

  // A successful save redirects, and every admin page then fires its footer's
  // ajax_getcitylist. Navigating on top of either is what "interrupted by
  // another navigation" means, so let the request finish first.
  await settle(page);
}

test('the add form offers the same kinds registration does', async ({ page }) => {
  await page.goto('sadmin/employer/add');
  await expectNoServerError(page);

  // The value is the number the database stores, not a word.
  const options = page.locator('select[name="emp_kind"] option:not([value=""])');
  await expect(options).toHaveCount(2);
  await expect(options).toHaveText([/Owner/, /Manager/]);
  expect(await options.evaluateAll((els) => els.map((e) => e.getAttribute('value'))))
    .toEqual(['1', '2']);
});

test('adding from a kind list starts on that kind', async ({ page }) => {
  await page.goto('sadmin/employer/add?kind=owner');
  await expect(page.locator('select[name="emp_kind"]')).toHaveValue('1');
});

test('neither kind is asked for a location, and only a manager for a group', async ({ page }) => {
  await page.goto('sadmin/employer/add');

  const address = page.locator('textarea[name="u_address1"]');
  const group = page.locator('.grouponly').first();

  // An owner names the group and adds its locations afterwards, so there is no
  // address here for one to be built from.
  await page.selectOption('select[name="emp_kind"]', '1');
  await expect(address).toBeHidden();
  await expect(group).toBeHidden();
  await expect(page.locator('select[name="u_store_id"]')).toBeHidden();
  await expect(page.locator('input[name="u_comp_name"]')).toBeVisible();
  await expect(page.locator('#compnamelbl')).toHaveText('Corporate Group Name');

  // A manager picks one of their group's existing stores, exactly as on public
  // registration, so they are asked for the group and the store - and for no
  // address at all, because the store they pick already has one.
  await page.selectOption('select[name="emp_kind"]', '2');
  await expect(address).toBeHidden();
  await expect(group).toBeVisible();
  await expect(page.locator('select[name="u_store_id"]')).toBeVisible();
  await expect(page.locator('input[name="u_comp_name"]')).toBeHidden();

  // A hidden field may not keep `required`, or the browser blocks the submit
  // without ever saying which field it is unhappy about.
  expect(await address.evaluate((el) => /** @type {HTMLTextAreaElement} */ (el).required)).toBe(false);
});

test('the edit form asks a manager exactly what the add form asks', async ({ page }) => {
  await page.goto(`sadmin/employer/edit/${ids.manager}`);
  await expectNoServerError(page);

  await expect(page.locator('select[name="emp_kind"]')).toHaveValue('2');

  // The store they run, as a dropdown of their group's locations - not a name
  // typed by hand. The edit form used to offer the name field instead, so the
  // same account looked like two different kinds of record depending on which
  // screen it was opened from.
  const store = page.locator('select[name="u_store_id"]');
  await expect(store).toBeVisible();
  await expect(page.locator('input[name="u_comp_name"]')).toBeHidden();
  await expect(page.locator('.grouponly').first()).toBeVisible();
  await expect(page.locator('textarea[name="u_address1"]')).toBeHidden();

  // "0 store(s) on this account" is true of a manager and says the opposite of
  // what it means, so it is not shown to one.
  await expect(page.getByText(/store\(s\) on this account/)).toBeHidden();

  // Opened on the store the account already holds, so saving without touching
  // it keeps that store rather than clearing it.
  await expect(store).toHaveValue(scalar(`SELECT u_store_id FROM users WHERE u_id = ${ids.manager};`));
});

test('a manager saved from the edit form keeps the shape of one', async ({ page }) => {
  const before = scalar(`SELECT u_store_id FROM users WHERE u_id = ${ids.manager};`);
  const storesBefore = count('store', '1 = 1');

  await page.goto(`sadmin/employer/edit/${ids.manager}`);
  await page.fill('input[name="u_phone"]', '4165550913');
  await save(page);

  expect(scalar(`SELECT u_phone FROM users WHERE u_id = ${ids.manager};`)).toBe('4165550913');
  expect(scalar(`SELECT u_store_id FROM users WHERE u_id = ${ids.manager};`),
    'the store is kept, not cleared').toBe(before);

  // A manager types no name of their own: the one on the login is the store
  // they run, copied across the way registration does it, because that is what
  // the employer list and the shift form's employer dropdown display.
  expect(scalar(`SELECT u_comp_name FROM users WHERE u_id = ${ids.manager};`))
    .toBe(scalar(`SELECT s_name FROM store WHERE s_id = ${before};`));
  expect(scalar(`SELECT u_address1 FROM users WHERE u_id = ${ids.manager};`))
    .toBe(scalar(`SELECT s_address FROM store WHERE s_id = ${before};`));

  // The account has no store of its own and must not be given one built from
  // those copied columns - the store it runs belongs to the group.
  expect(count('store', `u_id = ${ids.manager}`)).toBe(0);
  expect(count('store', '1 = 1')).toBe(storesBefore);
});

test('moving a manager to another store on the edit form saves that store', async ({ page }) => {
  // The group's other location.
  const moved = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Kindform Manager Store Two';");

  await page.goto(`sadmin/employer/edit/${ids.manager}`);
  await settle(page);
  await page.selectOption('select[name="u_store_id"]', moved);
  await save(page);

  expect(scalar(`SELECT u_store_id FROM users WHERE u_id = ${ids.manager};`)).toBe(moved);
});

test('a multi-store owner added here owns no store yet, and can add them later', async ({ page }) => {
  const email = 'e2e.kindform.newmulti@example.com';

  await page.goto('sadmin/employer/add');
  await page.selectOption('select[name="emp_kind"]', '1');
  await page.fill('input[name="u_fname"]', 'New');
  await page.fill('input[name="u_lname"]', 'Multi');
  await page.fill('input[name="u_comp_name"]', 'E2E Kindform New Multi');
  await page.fill('input[name="u_email"]', email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550910');
  await page.selectOption('select[name="u_status"]', '1');

  await save(page);

  const id = scalar(`SELECT u_id FROM users WHERE u_email = '${email}';`);
  expect(id).not.toBe('');
  expect(scalar(`SELECT u_emp_role FROM users WHERE u_id = ${id};`)).toBe('1');
  expect(scalar(`SELECT u_parent_id FROM users WHERE u_id = ${id};`)).toBe('0');

  // No store: a multi-store owner adds their locations afterwards, and the
  // form did not ask for an address to build one from.
  expect(count('store', `u_id = ${id}`)).toBe(0);
});

test('a manager is attached to the corporate group that was chosen', async ({ page }) => {
  const email = 'e2e.kindform.newmanager@example.com';

  await page.goto('sadmin/employer/add');
  await page.selectOption('select[name="emp_kind"]', '2');

  // Choosing the group fills the store list from that group's locations.
  const storeId = await pickGroupStore(page, ids.group);

  await page.fill('input[name="u_fname"]', 'New');
  await page.fill('input[name="u_lname"]', 'Manager');
  await page.fill('input[name="u_email"]', email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550911');
  await page.selectOption('select[name="u_status"]', '1');

  const storesBefore = count('store', '1 = 1');

  await save(page);

  const id = scalar(`SELECT u_id FROM users WHERE u_email = '${email}';`);
  expect(id).not.toBe('');
  expect(scalar(`SELECT u_emp_role FROM users WHERE u_id = ${id};`)).toBe('2');
  expect(scalar(`SELECT u_parent_id FROM users WHERE u_id = ${id};`)).toBe(String(ids.group));

  // The store they picked, and no new one: a manager joins a location the
  // group already has, which is what registration does.
  expect(scalar(`SELECT u_store_id FROM users WHERE u_id = ${id};`)).toBe(String(storeId));
  expect(count('store', `u_id = ${id}`), 'a manager owns no store').toBe(0);
  expect(count('store', '1 = 1'), 'and none was created').toBe(storesBefore);

  // The store's own name and address, copied onto the login exactly as
  // registration saves them - account-parity.spec.js holds the two paths to
  // each other. This form used to leave both blank, which made the account a
  // nameless row everywhere an employer is listed.
  expect(scalar(`SELECT u_comp_name FROM users WHERE u_id = ${id};`))
    .toBe(scalar(`SELECT s_name FROM store WHERE s_id = ${storeId};`));
  expect(scalar(`SELECT u_address1 FROM users WHERE u_id = ${id};`))
    .toBe(scalar(`SELECT s_address FROM store WHERE s_id = ${storeId};`));
});

test('a manager cannot be attached to a group that was never offered', async ({ page }) => {
  const email = 'e2e.kindform.badgroup@example.com';

  await page.goto('sadmin/employer/add');
  await page.selectOption('select[name="emp_kind"]', '2');

  // An id that is not one of the approved multi-store owners, forced into the
  // dropdown the way a hand-edited form would.
  await page.locator('select[name="u_parent_id"]').evaluate((el) => {
    const select = /** @type {HTMLSelectElement} */ (el);
    const option = document.createElement('option');
    option.value = '999999';
    option.textContent = 'Not a group';
    select.appendChild(option);
    select.value = '999999';
  });

  // And a store id to match, forced in the same way - otherwise the browser
  // stops at the empty required dropdown and the controller, which is what
  // this test is about, is never reached.
  await page.locator('select[name="u_store_id"]').evaluate((el) => {
    const select = /** @type {HTMLSelectElement} */ (el);
    const option = document.createElement('option');
    option.value = '999999';
    option.textContent = 'Not a store';
    select.appendChild(option);
    select.value = '999999';
  });

  await page.fill('input[name="u_fname"]', 'Bad');
  await page.fill('input[name="u_lname"]', 'Group');
  await page.fill('input[name="u_email"]', email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550912');

  await save(page);

  // The alert, not the body: "Corporate Group" is also the label of the very
  // dropdown under test, so a body-wide match would pass without the refusal.
  await expect(page.locator('.alert-danger')).toContainText(/choose the corporate group/i);
  expect(count('users', `u_email = '${email}'`)).toBe(0);
});

test('an employer from before multi-store is converted to a multi-store owner', async ({ page }) => {
  // The starting point: role 0, which belongs to no kind.
  expect(scalar(`SELECT u_emp_role FROM users WHERE u_id = ${ids.legacy};`)).toBe('0');

  await page.goto(`sadmin/employer/edit/${ids.legacy}`);
  await expectNoServerError(page);

  // The dropdown reflects what the account is now: nothing.
  await expect(page.locator('select[name="emp_kind"]')).toHaveValue('');

  await page.selectOption('select[name="emp_kind"]', '1');
  await save(page);

  expect(scalar(`SELECT u_emp_role FROM users WHERE u_id = ${ids.legacy};`)).toBe('1');
  expect(scalar(`SELECT u_parent_id FROM users WHERE u_id = ${ids.legacy};`)).toBe('0');

  // The store they already had is untouched - converting classifies the
  // account, it does not move its locations.
  expect(count('store', `u_id = ${ids.legacy}`)).toBe(1);

  // And the address on the login is kept: a shift created outside the store
  // flow still reads its location off those columns.
  expect(scalar(`SELECT u_address1 FROM users WHERE u_id = ${ids.legacy};`)).toBe('9 Kind Street');

  // It now appears under its kind, and no longer reads "Not set".
  await page.goto('sadmin/employer/owner');
  await expect(page.locator('body')).toContainText('E2E Kindform Legacy');
});

test('a converted employer keeps showing under the kind it was given', async ({ page }) => {
  await page.goto(`sadmin/employer/edit/${ids.legacy}`);

  // Re-opening the record shows the kind that was just saved, not a blank.
  await expect(page.locator('select[name="emp_kind"]')).toHaveValue('1');
});

test('an owner holding several stores cannot become a manager', async ({ page }) => {
  expect(count('store', `u_id = ${ids.busy}`)).toBe(3);

  await page.goto(`sadmin/employer/edit/${ids.busy}`);
  await page.selectOption('select[name="emp_kind"]', '2');

  // A manager answers to a group and runs one of its stores, and both selects
  // are required once shown - so fill them, or the browser stops the submit and
  // the controller never gets to refuse the conversion, which is what this test
  // is about.
  await pickGroupStore(page, ids.group);
  await save(page);

  await expect(page.locator('.alert-danger')).toContainText(/3 stores/i);

  // Refused, and nothing changed.
  expect(scalar(`SELECT u_emp_role FROM users WHERE u_id = ${ids.busy};`)).toBe('1');
  expect(count('store', `u_id = ${ids.busy}`)).toBe(3);
});

test('a corporate group that managers answer to cannot stop being one', async ({ page }) => {
  const managerEmail = 'e2e.kindform.attached@example.com';

  const managerId = seedEmployer(managerEmail, {
    role: 2,
    parent: ids.group,
    company: 'E2E Kindform Attached Manager',
  });

  await page.goto(`sadmin/employer/edit/${ids.group}`);
  await page.selectOption('select[name="emp_kind"]', '2');
  await pickGroupStore(page, ids.busy);
  await save(page);

  await expect(page.locator('.alert-danger')).toContainText(/manager account/i);

  expect(scalar(`SELECT u_emp_role FROM users WHERE u_id = ${ids.group};`)).toBe('1');
  // The manager still points at a group that still is one.
  expect(scalar(`SELECT u_parent_id FROM users WHERE u_id = ${managerId};`)).toBe(String(ids.group));
});
