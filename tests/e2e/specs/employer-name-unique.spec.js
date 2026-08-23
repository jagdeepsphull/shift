// @ts-check
/**
 * One name, one employer.
 *
 * `u_comp_name` is what every screen calls an employer by - the employer list,
 * the employer dropdown on both shift forms, the store picker and the booking
 * e-mails all read it. Two accounts holding the same name leaves an
 * administrator picking blind between identical rows, so the name has to be
 * unique: registration refuses one that is taken, and so does the back-office
 * employer form, from the same helper (`employerNameTaken()`).
 *
 * Both refusals are checked here, along with the two ways the rule could be
 * too strict: a free name must still go through, and an account must be able
 * to be re-saved without its own name reading as a duplicate of itself.
 *
 * A manager is not covered because a manager is never asked for a name: the
 * store they picked already has one, and it is copied onto their row.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { readCaptchaCode } = require('../helpers/session');
const { query, scalar, count } = require('../helpers/db');

const PREFIX = 'e2e.uniqname.';
const PASSWORD = 'E2eTest@12345';
const PHONE = '4160000777';

/** The name that is already spoken for, and a second owner to move onto it. */
const TAKEN = 'E2E Unique Name Group';
const OTHER = 'E2E Unique Name Other';

/** The same name as far as the rule is concerned: case and spacing are not it. */
const TAKEN_RETYPED = '  e2e UNIQUE name group ';

/** @type {{taken: string, other: string}} */
const ids = { taken: '', other: '' };

const cleanup = () => {
  query(`
    DELETE FROM store WHERE u_id IN (
      SELECT u_id FROM (SELECT u_id FROM users WHERE u_userid LIKE '${PREFIX}%') x);
  `);
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
};

/**
 * An approved owner holding one name, seeded straight into the table.
 *
 * @param {string} email
 * @param {string} name
 * @returns {string} the new u_id
 */
function seedOwner(email, name) {
  query(`INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname,
                            u_lname, u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo,
                            u_photo, u_provice, u_city, u_address1, u_pincode, u_phone, u_email,
                            u_terms, u_status, u_collartype, created, modified, u_login_attempt,
                            u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
         VALUES (1, 0, 1, 0, '${email}', 'Unique', 'Tester', MD5('x'), '${name}', 0, '', '',
                 '', 0, 0, '', '', '${PHONE}', '${email}', 1, 1, 0, NOW(), NOW(), 0, NOW(),
                 '127.0.0.1', '', '1970-01-01 00:00:00');`);

  return scalar(`SELECT u_id FROM users WHERE u_userid = '${email}';`);
}

test.beforeAll(() => {
  cleanup();
  ids.taken = seedOwner(`${PREFIX}taken@example.com`, TAKEN);
  ids.other = seedOwner(`${PREFIX}other@example.com`, OTHER);
});

test.afterAll(cleanup);

/**
 * Fill in the public form as an Owner and submit it.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{email: string, company: string}} opts
 */
async function registerOwner(page, opts) {
  await page.goto('front/register');
  await settle(page);

  const code = String(await readCaptchaCode(page.context()));
  expect(code, 'verification code should be in the session').toBeTruthy();

  const form = page.locator('#register-form');

  await page.selectOption('#usrtpe', '1');
  await page.fill('#u_comp_name', opts.company);
  await page.fill('#u_fname', 'Unique');
  await page.fill('#u_lname', 'Tester');
  await page.fill('#u_email', opts.email);
  await page.fill('#u_phone', PHONE);
  await page.fill('#mainpassword', PASSWORD);
  await page.fill('#conf_password', PASSWORD);
  await form.locator('input[name="captcha"]').fill(code);

  await Promise.all([
    page.waitForLoadState('load'),
    form.locator('[name="signupSubmit"]').click(),
  ]);
  await settle(page);
}

/**
 * Fill the back-office employer form as an Owner and save it.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{email: string, company: string}} opts
 */
async function addOwner(page, opts) {
  await page.goto('sadmin/employer/add?kind=owner');
  await expectNoServerError(page);

  await page.selectOption('select[name="emp_kind"]', '1');
  await page.fill('input[name="u_comp_name"]', opts.company);
  await page.fill('input[name="u_fname"]', 'Unique');
  await page.fill('input[name="u_lname"]', 'Tester');
  await page.fill('input[name="u_email"]', opts.email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', PHONE);

  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);
  await settle(page);
}

/** Submit whichever back-office form is open. */
async function save(page) {
  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);
  await settle(page);
}

test('public registration refuses a corporate group name that is taken', async ({ page }) => {
  const email = `${PREFIX}public.dupe@example.com`;

  await registerOwner(page, { email, company: TAKEN_RETYPED });

  await expect(page.locator('body')).toContainText(/already registered to another account/i);
  expect(count('users', `u_userid = '${email}'`), 'no account was created').toBe(0);
});

test('public registration accepts a corporate group name nobody holds', async ({ page }) => {
  const email = `${PREFIX}public.free@example.com`;

  await registerOwner(page, { email, company: 'E2E Unique Name Free' });

  await expect(page.locator('body')).toContainText(/Registration successful/i);
  expect(count('users', `u_userid = '${email}'`), 'the account was created').toBe(1);
});

test('the back office refuses an employer name that is taken', async ({ page }) => {
  const email = `${PREFIX}admin.dupe@example.com`;

  await loginAsAdmin(page);
  await addOwner(page, { email, company: TAKEN_RETYPED });

  await expect(page.locator('body')).toContainText(/already registered to another account/i);
  expect(count('users', `u_userid = '${email}'`), 'no account was created').toBe(0);
});

test('an account can be re-saved under the name it already holds', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto(`sadmin/employer/edit/${ids.taken}`);
  await expectNoServerError(page);

  // Nothing about the name is touched - this is the administrator changing
  // something else on an account whose own name the rule would otherwise read
  // as a duplicate of itself.
  await page.fill('input[name="u_lname"]', 'Resaved');
  await save(page);

  await expect(page.locator('body')).not.toContainText(/already registered to another account/i);
  expect(scalar(`SELECT u_lname FROM users WHERE u_id = ${ids.taken};`)).toBe('Resaved');
  expect(scalar(`SELECT u_comp_name FROM users WHERE u_id = ${ids.taken};`)).toBe(TAKEN);
});

test('the back office refuses moving an account onto another employer name', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto(`sadmin/employer/edit/${ids.other}`);
  await expectNoServerError(page);

  await page.fill('input[name="u_comp_name"]', TAKEN_RETYPED);
  await save(page);

  await expect(page.locator('body')).toContainText(/already registered to another account/i);
  expect(scalar(`SELECT u_comp_name FROM users WHERE u_id = ${ids.other};`), 'the name is unchanged').toBe(OTHER);
});
