// @ts-check
/**
 * The back-office add forms accept what the public ones accept, and reject what
 * they reject.
 *
 * Both admin forms filtered typing with `onkeydown="return /[a-z, ]/i.test(...)"`,
 * which silently swallows every key that is not a letter, comma or space. A
 * hyphen, an apostrophe and a bracket are all in `NAME_PATTERN` and all accepted
 * by public registration - the same defect was fixed there and missed here, so
 * an administrator could not type O'Brien or Smith-Jones at all.
 *
 * The other half of parity is the server: the admin forms applied no name rule,
 * so a name full of digits went straight in on the one column the public form
 * guards.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';

/** Names the public form accepts and the old key filter made impossible to type. */
const REAL_NAMES = { first: "Siobhan-Marie", last: "O'Brien (Jr.)" };

function removeSeeded() {
  query(`
    DELETE FROM store
     WHERE u_id IN (SELECT u_id FROM (SELECT u_id FROM users WHERE u_email LIKE 'e2e.parity.%@example.com') x);
  `);
  query("DELETE FROM users WHERE u_email LIKE 'e2e.parity.%@example.com';");
}

test.beforeAll(removeSeeded);
test.afterAll(removeSeeded);

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @param {string} value
 */
async function typeInto(page, selector, value) {
  // Typed key by key, not set through the DOM: an `onkeydown` filter only shows
  // up when keys are actually pressed, which is the whole point here.
  await page.locator(selector).click();
  await page.locator(selector).pressSequentially(value, { delay: 1 });
}

/** @param {import('@playwright/test').Page} page */
async function chooseProvinceAndCity(page) {
  const province = page.locator('select[name="u_provice"]');
  const value = await province.locator('option:not([value=""])').first().getAttribute('value');

  const cityLoaded = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));
  await province.selectOption(String(value));
  await cityLoaded;

  const city = page.locator('select[name="u_city"]');
  await city.selectOption(String(await city.locator('option:not([value=""])').first().getAttribute('value')));

  const licence = page.locator('select[name="u_l_provice"]');
  if (await licence.count()) await licence.selectOption(String(value));
}

/** @param {import('@playwright/test').Page} page */
async function save(page) {
  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);
  await settle(page);
}

test('an employer name with a hyphen, apostrophe and brackets can be typed and saved', async ({ page }) => {
  const email = 'e2e.parity.employer@example.com';

  await page.goto('sadmin/employer/add');

  await typeInto(page, 'input[name="u_fname"]', REAL_NAMES.first);
  await typeInto(page, 'input[name="u_lname"]', REAL_NAMES.last);

  // Typed, not assigned - so this fails if the key filter comes back.
  await expect(page.locator('input[name="u_fname"]')).toHaveValue(REAL_NAMES.first);
  await expect(page.locator('input[name="u_lname"]')).toHaveValue(REAL_NAMES.last);

  // An Owner: neither employer kind is asked for a location any more, so there
  // is no address, licence or city on this form to fill in.
  await page.selectOption('select[name="emp_kind"]', '1');
  await page.fill('input[name="u_comp_name"]', 'E2E Parity Employer');
  await page.fill('input[name="u_email"]', email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550201');
  await page.selectOption('select[name="u_status"]', '1');

  await save(page);

  expect(scalar(`SELECT u_fname FROM users WHERE u_email = '${email}';`)).toBe(REAL_NAMES.first);
  expect(scalar(`SELECT u_lname FROM users WHERE u_email = '${email}';`)).toBe(REAL_NAMES.last);
});

test('an applicant name with a hyphen, apostrophe and brackets can be typed and saved', async ({ page }) => {
  const email = 'e2e.parity.applicant@example.com';

  await page.goto('sadmin/applicant/add');

  await typeInto(page, 'input[name="u_fname"]', REAL_NAMES.first);
  await typeInto(page, 'input[name="u_lname"]', REAL_NAMES.last);

  await expect(page.locator('input[name="u_fname"]')).toHaveValue(REAL_NAMES.first);
  await expect(page.locator('input[name="u_lname"]')).toHaveValue(REAL_NAMES.last);

  const subtype = page.locator('select[name="u_usersubtype"]');
  await subtype.selectOption(String(await subtype.locator('option:not([value=""])').first().getAttribute('value')));

  await page.fill('input[name="u_licence_no"]', 'E2E-P-2');
  await page.fill('input[name="u_email"]', email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550202');
  await page.fill('textarea[name="u_address1"]', '2 Parity Street');
  await chooseProvinceAndCity(page);
  await page.fill('input[name="u_pincode"]', 'M5A 1A1');
  await page.selectOption('select[name="u_status"]', '1');

  await save(page);

  expect(scalar(`SELECT u_fname FROM users WHERE u_email = '${email}';`)).toBe(REAL_NAMES.first);
  expect(scalar(`SELECT u_lname FROM users WHERE u_email = '${email}';`)).toBe(REAL_NAMES.last);
});

test('a name full of digits is refused by the server, as on the public form', async ({ page }) => {
  const email = 'e2e.parity.digits@example.com';

  await page.goto('sadmin/applicant/add');

  await page.fill('input[name="u_fname"]', 'A1234');
  await page.fill('input[name="u_lname"]', '5678');

  const subtype = page.locator('select[name="u_usersubtype"]');
  await subtype.selectOption(String(await subtype.locator('option:not([value=""])').first().getAttribute('value')));

  await page.fill('input[name="u_licence_no"]', 'E2E-P-3');
  await page.fill('input[name="u_email"]', email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550203');
  await page.fill('textarea[name="u_address1"]', '3 Parity Street');
  await chooseProvinceAndCity(page);
  await page.fill('input[name="u_pincode"]', 'M5A 1A1');

  await save(page);

  await expect(page.locator('.alert-danger')).toContainText(/first name|last name/i);
  expect(count('users', `u_email = '${email}'`)).toBe(0);
});

test('the applicant form offers the same categories as the public one', async ({ page }) => {
  // "Different categories" on both forms is the `shift_for` list, so the two
  // have to offer the same set - the admin one is not a hard-coded copy.
  const expected = query("SELECT sf_name FROM shift_for WHERE sf_status = 1 ORDER BY sf_name ASC;")
    .split('\n')
    .filter(Boolean);

  await page.goto('sadmin/applicant/add');
  const admin = await page.locator('select[name="u_usersubtype"] option:not([value=""])').allTextContents();

  await page.goto('front/signup');
  const publicList = await page.locator('#register select[name="u_usersubtype"] option:not([value=""])').allTextContents();

  const normalise = (/** @type {string[]} */ list) => list.map((t) => t.trim()).sort();

  expect(normalise(admin)).toEqual(normalise(expected));
  expect(normalise(publicList)).toEqual(normalise(expected));
});
