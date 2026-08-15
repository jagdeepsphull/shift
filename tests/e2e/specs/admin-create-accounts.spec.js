// @ts-check
/**
 * Accounts created from the back office have to be able to log in.
 *
 * The login screen looks an account up by `users.u_userid`. Public registration
 * fills that column from the e-mail address; neither admin "add" form ever did,
 * so an employer or applicant added by an administrator was saved with a blank
 * login id and could never sign in - the password was right, the lookup found
 * nothing. Three such rows were in the live table.
 *
 * These tests drive the real forms and then finish the journey the account was
 * created for: signing in on the public site.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError, settle } = require('../helpers/admin');
const { loginAsFrontUser } = require('../helpers/front');
const { query, scalar, count } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';

const NEW_EMPLOYER = 'e2e.made.employer@example.com';
const NEW_APPLICANT = 'e2e.made.applicant@example.com';
const TAKEN = 'e2e.made.taken@example.com';

function removeSeeded() {
  // Stores first: an individual owner added through the form gets one, and it
  // would otherwise outlive the account it belongs to.
  query(`
    DELETE FROM store
     WHERE u_id IN (
       SELECT u_id FROM (
         SELECT u_id FROM users WHERE u_email LIKE 'e2e.made.%@example.com'
       ) x
     );
  `);
  query(`DELETE FROM users WHERE u_userid LIKE 'e2e.made.%@example.com' OR u_email LIKE 'e2e.made.%@example.com';`);
}

test.beforeAll(() => {
  removeSeeded();

  // An account that already owns the address the duplicate test tries to reuse.
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress,
       reset_token, token_expiry)
    VALUES
      (1, 0, 0, 0, '${TAKEN}', 'Taken', 'E2E', MD5('${PASSWORD}'),
       'E2E Made Taken', 0, '', '', '', 0, 0,
       '', '', '0000000000', '${TAKEN}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);
});

test.afterAll(() => {
  removeSeeded();
});

/**
 * The province/city pair on both add forms is a dependent dropdown: the city
 * list is fetched over ajax once a province is chosen.
 *
 * @param {import('@playwright/test').Page} page
 */
async function chooseProvinceAndCity(page) {
  const province = page.locator('select[name="u_provice"]');
  const value = await province.locator('option:not([value=""])').first().getAttribute('value');

  const cityLoaded = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));
  await province.selectOption(String(value));
  await cityLoaded;

  const city = page.locator('select[name="u_city"]');
  await expect(city.locator('option')).not.toHaveCount(1);
  const cityValue = await city.locator('option:not([value=""])').first().getAttribute('value');
  await city.selectOption(String(cityValue));

  // The licence province is a plain select on both forms.
  const licence = page.locator('select[name="u_l_provice"]');
  if (await licence.count()) {
    await licence.selectOption(String(value));
  }
}

test('an employer added from the back office can sign in', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('sadmin/employer/add');
  await expectNoServerError(page);

  // An Owner. Neither kind is asked for a location any more - an owner's
  // belongs to each store they add afterwards - so there is none to fill in.
  await page.selectOption('select[name="emp_kind"]', '1');
  await page.fill('input[name="u_fname"]', 'Made');
  await page.fill('input[name="u_lname"]', 'Employer');
  await page.fill('input[name="u_comp_name"]', 'E2E Made Employer');
  await page.fill('input[name="u_email"]', NEW_EMPLOYER);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550101');
  await page.selectOption('select[name="u_status"]', '1');

  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);

  // Saved as an employer, and - the point of the fix - with a login id.
  expect(count('users', `u_email = '${NEW_EMPLOYER}'`)).toBe(1);
  expect(scalar(`SELECT u_userid FROM users WHERE u_email = '${NEW_EMPLOYER}';`)).toBe(NEW_EMPLOYER);
  expect(scalar(`SELECT u_usertype FROM users WHERE u_email = '${NEW_EMPLOYER}';`)).toBe('1');

  // Saved as the kind that was chosen, not left on the role 0 that accounts
  // carried before the kinds existed.
  expect(scalar(`SELECT u_emp_role FROM users WHERE u_email = '${NEW_EMPLOYER}';`)).toBe('1');
  expect(scalar(`SELECT u_parent_id FROM users WHERE u_email = '${NEW_EMPLOYER}';`)).toBe('0');

  // And no store: an owner adds their locations afterwards from Manage Stores,
  // so there is no address here for one to be built from.
  expect(count('store', `u_id = (SELECT u_id FROM users WHERE u_email = '${NEW_EMPLOYER}')`)).toBe(0);

  // And the account actually works on the public site.
  await loginAsFrontUser(page, { user: NEW_EMPLOYER, pass: PASSWORD });
  await expect(page).toHaveURL(/\/employer\//);
  await expectNoServerError(page);
});

test('an applicant added from the back office can sign in', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('sadmin/applicant/add');
  await expectNoServerError(page);

  await page.fill('input[name="u_fname"]', 'Made');
  await page.fill('input[name="u_lname"]', 'Applicant');

  const subtype = page.locator('select[name="u_usersubtype"]');
  const subtypeValue = await subtype.locator('option:not([value=""])').first().getAttribute('value');
  await subtype.selectOption(String(subtypeValue));

  await page.fill('input[name="u_licence_no"]', 'E2E-LIC-1');
  await page.fill('input[name="u_email"]', NEW_APPLICANT);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550102');
  await page.fill('textarea[name="u_address1"]', '2 Made Street');
  await chooseProvinceAndCity(page);
  await page.fill('input[name="u_pincode"]', 'M5A 1A1');
  await page.selectOption('select[name="u_status"]', '1');

  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);

  expect(count('users', `u_email = '${NEW_APPLICANT}'`)).toBe(1);
  expect(scalar(`SELECT u_userid FROM users WHERE u_email = '${NEW_APPLICANT}';`)).toBe(NEW_APPLICANT);
  expect(scalar(`SELECT u_usertype FROM users WHERE u_email = '${NEW_APPLICANT}';`)).toBe('2');

  await loginAsFrontUser(page, { user: NEW_APPLICANT, pass: PASSWORD });
  await expect(page).toHaveURL(/\/applicant\//);
  await expectNoServerError(page);
});

test('an e-mail that is already a login id is refused, and nothing is inserted', async ({ page }) => {
  await loginAsAdmin(page);

  const before = count('users', `u_email = '${TAKEN}'`);
  expect(before).toBe(1);

  await page.goto('sadmin/employer/add');

  await page.selectOption('select[name="emp_kind"]', '1');
  await page.fill('input[name="u_fname"]', 'Duplicate');
  await page.fill('input[name="u_lname"]', 'Employer');
  await page.fill('input[name="u_comp_name"]', 'E2E Made Duplicate');
  await page.fill('input[name="u_email"]', TAKEN);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550103');
  await page.selectOption('select[name="u_status"]', '1');

  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);

  await expect(page.locator('body')).toContainText(/already taken/i);
  expect(count('users', `u_email = '${TAKEN}'`)).toBe(before);
});

test('no account is left without a login id', async () => {
  // The backfill migration repaired the rows the old forms created; the forms
  // no longer make new ones. Either regression would show up here.
  expect(count('users', "u_userid IS NULL OR u_userid = ''")).toBe(0);
});
