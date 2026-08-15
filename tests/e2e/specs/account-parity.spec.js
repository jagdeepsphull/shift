// @ts-check
/**
 * The same account, made two ways.
 *
 * Every kind of account can be created twice over: by the person themselves on
 * public registration, or by an administrator in the back office. The two
 * forms are separate screens on separate controllers, and what each writes has
 * drifted before - a manager added in the back office was left with no company
 * name and no address at all, because only registration copied them off the
 * store that was chosen. That account then read as an empty row on the employer
 * list and in the employer dropdown on both shift forms.
 *
 * So this file creates an Owner, a Manager and an Applicant down each path and
 * compares the rows they produce, column by column, on everything that is not
 * inherently per-account (id, e-mail, timestamps, the password hash's salt).
 *
 * It drives the public form explicitly - real province and city ids rather than
 * "whatever option 1 is" - so that both sides can be given the same answers and
 * any difference in the result is the application's, not the test's.
 * registration.spec.js covers the public form on its own terms; this one exists
 * only to hold the two paths to each other.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { readCaptchaCode } = require('../helpers/session');
const { query, scalar } = require('../helpers/db');
const {
  seedPharmacyGroup,
  removePharmacyGroup,
  seedGroupStores,
  removeGroupStores,
  REG_STORE_SELECT,
  REG_STORE_ENDPOINT,
  multiStoreMissing,
} = require('../helpers/stores');

const PREFIX = 'e2e.parity.';
const PASSWORD = 'E2eTest@12345';
const PHONE = '4160000555';
const ADDRESS = '55 Parity Street, Unit 2';
const POSTCODE = 'M5A 1A1';
const LICENCE = 'E2E-PAR-1';
const OWNER_NAME = 'E2E Parity Owner Group';

/** @type {{group: number, store: any, province: string, city: string, subtype: string}} */
const fixture = { group: 0, store: null, province: '0', city: '0', subtype: '0' };

const removeFixtures = () => {
  query(`
    DELETE FROM store WHERE u_id IN (
      SELECT u_id FROM (SELECT u_id FROM users WHERE u_userid LIKE '${PREFIX}%') x);
  `);
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
};

test.beforeAll(() => {
  removeFixtures();

  fixture.province = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;') || '0';
  fixture.city = scalar(`SELECT c_id FROM city WHERE c_status = 1 AND c_province = ${fixture.province} LIMIT 1;`) || '0';
  fixture.subtype = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_id LIMIT 1;') || '0';

  // A manager needs a corporate group with somewhere to work, on both forms.
  fixture.group = seedPharmacyGroup();
  fixture.store = seedGroupStores(fixture.group)[0];
});

test.afterAll(() => {
  removeFixtures();
  removeGroupStores();
  removePharmacyGroup();
});

test.beforeEach(() => {
  const missing = multiStoreMissing();
  test.skip(missing !== null, missing || '');
});

/**
 * Every column both paths are expected to agree on, read back as one string.
 *
 * Deliberately not: u_id, the e-mail and login id, created/modified, the
 * password hash (salted, so two hashes of the same password differ), the
 * status (the back office may set one; a registration is always pending) and
 * u_ipaddress. Everything else describes what kind of account this is, and
 * that must not depend on who filled the form in.
 *
 * @param {string} email
 * @returns {string|null}
 */
function shape(email) {
  const row = scalar(`
    SELECT CONCAT_WS('|',
             'usertype=',      u_usertype,
             'subtype=',       u_usersubtype,
             'role=',          u_emp_role,
             'parent=',        u_parent_id,
             'store=',         IFNULL(u_store_id, 0),
             'company=',       u_comp_name,
             'licence=',       u_licence_no,
             'l_province=',    u_l_provice,
             'province=',      u_provice,
             'city=',          u_city,
             'address=',       u_address1,
             'pincode=',       u_pincode,
             'phone=',         u_phone,
             'website=',       IFNULL(u_website, ''))
      FROM users WHERE u_userid = '${email}';
  `);

  return row === '' ? null : row;
}

/** How many stores the account owns - a manager owns none, an owner adds theirs later. */
const storesOwnedBy = (email) => Number(scalar(`
  SELECT COUNT(*) FROM store s JOIN users u ON u.u_id = s.u_id WHERE u.u_userid = '${email}';
`) || 0);

/**
 * Register through the public form.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{regType: string, email: string, company?: string, group?: boolean,
 *          location?: boolean, subtype?: boolean}} opts
 */
async function registerPublicly(page, opts) {
  await page.goto('front/register');
  await settle(page);

  const code = String(await readCaptchaCode(page.context()));
  expect(code, 'verification code should be in the session').toBeTruthy();

  const form = page.locator('#register-form');

  await page.selectOption('#usrtpe', opts.regType);

  if (opts.company !== undefined) await page.fill('#u_comp_name', opts.company);
  if (opts.subtype) await page.selectOption('#u_usersubtype', fixture.subtype);

  if (opts.group) {
    const stores = page.waitForResponse((r) => r.url().includes(REG_STORE_ENDPOINT));
    await page.selectOption('#u_parent_id', String(fixture.group));
    await stores;
    await page.selectOption(REG_STORE_SELECT, String(fixture.store.id));
  }

  await page.fill('#u_fname', 'Parity');
  await page.fill('#u_lname', 'Tester');
  await page.fill('#u_email', opts.email);
  await page.fill('#u_phone', PHONE);

  if (opts.location) {
    await page.selectOption('#u_l_provice', fixture.province);
    await page.fill('#u_licence_no', LICENCE);
    await form.locator('textarea[name="u_address1"]').fill(ADDRESS);

    const cities = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));
    await page.selectOption('#provincelist', fixture.province);
    await cities;
    await page.selectOption('#city', fixture.city);

    await form.locator('input[name="u_pincode"]').fill(POSTCODE);
  }

  await page.fill('#mainpassword', PASSWORD);
  await page.fill('#conf_password', PASSWORD);
  await form.locator('input[name="captcha"]').fill(code);

  await Promise.all([
    page.waitForLoadState('load'),
    form.locator('[name="signupSubmit"]').click(),
  ]);
  await settle(page);

  const flash = (await page.locator('.alert').first().textContent().catch(() => '')) || '';
  expect(flash, `registration was refused: ${flash.trim()}`).toContain('Registration successful');
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

/**
 * Fill the shared half of the back-office employer form.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} email
 */
async function fillEmployerBasics(page, email) {
  await page.fill('input[name="u_fname"]', 'Parity');
  await page.fill('input[name="u_lname"]', 'Tester');
  await page.fill('input[name="u_email"]', email);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', PHONE);
}

test('an Owner is the same account either way', async ({ page }) => {
  const registered = `${PREFIX}owner.public@example.com`;
  const created = `${PREFIX}owner.admin@example.com`;

  await registerPublicly(page, { regType: '1', email: registered, company: OWNER_NAME });
  await expectNoServerError(page);

  await loginAsAdmin(page);
  await page.goto('sadmin/employer/add');
  await page.selectOption('select[name="emp_kind"]', '1');
  await page.fill('input[name="u_comp_name"]', OWNER_NAME);
  await fillEmployerBasics(page, created);
  await save(page);
  await expectNoServerError(page);

  const fromRegistration = shape(registered);
  const fromBackOffice = shape(created);

  expect(fromRegistration, 'the registration created an account').not.toBeNull();
  expect(fromBackOffice, 'the back office created an account').not.toBeNull();
  expect(fromBackOffice).toBe(fromRegistration);

  // And what that shape actually is: a multi-store owner, named, with no
  // location of their own and no store yet - they add those afterwards.
  expect(fromRegistration).toContain(`company=|${OWNER_NAME}`);
  expect(fromRegistration).toContain('role=|1');
  expect(fromRegistration).toContain('address=||');
  expect(storesOwnedBy(registered), 'no store yet, either way').toBe(0);
  expect(storesOwnedBy(created)).toBe(0);
});

test('a Manager is the same account either way', async ({ page }) => {
  const registered = `${PREFIX}manager.public@example.com`;
  const created = `${PREFIX}manager.admin@example.com`;

  await registerPublicly(page, { regType: '2', email: registered, group: true });
  await expectNoServerError(page);

  await loginAsAdmin(page);
  await page.goto('sadmin/employer/add');
  await page.selectOption('select[name="emp_kind"]', '2');

  const stores = page.waitForResponse((r) => r.url().includes('ajax_getstorelist'));
  await page.selectOption('select[name="u_parent_id"]', String(fixture.group));
  await stores;
  await page.selectOption('select[name="u_store_id"]', String(fixture.store.id));

  await fillEmployerBasics(page, created);
  await save(page);
  await expectNoServerError(page);

  const fromRegistration = shape(registered);
  const fromBackOffice = shape(created);

  expect(fromRegistration, 'the registration created an account').not.toBeNull();
  expect(fromBackOffice, 'the back office created an account').not.toBeNull();
  expect(fromBackOffice).toBe(fromRegistration);

  // The store they picked, and its details copied onto the login - which is
  // what every screen that names an employer reads. A back-office manager was
  // left blank here, so this is the assertion that regression would trip.
  expect(fromRegistration).toContain(`store=|${fixture.store.id}`);
  expect(fromRegistration).toContain(`parent=|${fixture.group}`);
  expect(fromBackOffice).toContain(`company=|${fixture.store.name}`);
  expect(fromBackOffice).toContain(`address=|${fixture.store.address}`);
  expect(fromBackOffice).toContain(`licence=|${fixture.store.number}`);

  // Neither owns the store: it stays the group's.
  expect(storesOwnedBy(registered)).toBe(0);
  expect(storesOwnedBy(created)).toBe(0);
  expect(scalar(`SELECT u_id FROM store WHERE s_id = ${fixture.store.id};`)).toBe(String(fixture.group));
});

test('an Applicant is the same account either way', async ({ page }) => {
  const registered = `${PREFIX}applicant.public@example.com`;
  const created = `${PREFIX}applicant.admin@example.com`;

  await registerPublicly(page, {
    regType: '3',
    email: registered,
    subtype: true,
    location: true,
  });
  await expectNoServerError(page);

  await loginAsAdmin(page);
  await page.goto('sadmin/applicant/add');
  await page.selectOption('select[name="u_usersubtype"]', fixture.subtype);
  await page.selectOption('select[name="u_l_provice"]', fixture.province);
  await page.fill('input[name="u_licence_no"]', LICENCE);

  await page.fill('input[name="u_fname"]', 'Parity');
  await page.fill('input[name="u_lname"]', 'Tester');
  await page.fill('input[name="u_email"]', created);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', PHONE);

  await page.locator('textarea[name="u_address1"]').fill(ADDRESS);

  const cities = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));
  await page.selectOption('select[name="u_provice"]', fixture.province);
  await cities;
  await page.selectOption('select[name="u_city"]', fixture.city);
  await page.fill('input[name="u_pincode"]', POSTCODE);

  await save(page);
  await expectNoServerError(page);

  const fromRegistration = shape(registered);
  const fromBackOffice = shape(created);

  expect(fromRegistration, 'the registration created an account').not.toBeNull();
  expect(fromBackOffice, 'the back office created an account').not.toBeNull();
  expect(fromBackOffice).toBe(fromRegistration);

  // An applicant is the other side of the site, carries the category they
  // chose, and keeps the address they typed.
  expect(fromRegistration).toContain('usertype=|2');
  expect(fromRegistration).toContain(`subtype=|${fixture.subtype}`);
  expect(fromRegistration).toContain(`address=|${ADDRESS}`);
  expect(storesOwnedBy(created), 'an applicant is not an employer').toBe(0);
});

test('every account made either way can sign in once approved', async ({ page }) => {
  const emails = [
    `${PREFIX}owner.public@example.com`,
    `${PREFIX}owner.admin@example.com`,
    `${PREFIX}manager.public@example.com`,
    `${PREFIX}manager.admin@example.com`,
    `${PREFIX}applicant.public@example.com`,
    `${PREFIX}applicant.admin@example.com`,
  ];

  for (const email of emails) {
    const id = scalar(`SELECT u_id FROM users WHERE u_userid = '${email}';`);
    test.skip(id === '', `${email} was not created by the tests above`);

    query(`UPDATE users SET u_status = 1 WHERE u_id = ${id};`);

    await page.goto('front/login');
    await settle(page);

    const code = String(await readCaptchaCode(page.context()));
    const form = page.locator('#login-form');

    await form.locator('input[name="username"]').fill(email);
    await form.locator('input[name="password"]').fill(PASSWORD);
    await form.locator('input[name="captcha"]').fill(code);

    await Promise.all([
      page.waitForLoadState('load'),
      form.locator('[name="loginSubmit"]').click(),
    ]);
    await settle(page);

    // Employers land on their shifts, applicants on the ones they applied for -
    // and the back office must not have produced an account that cannot get in
    // at all, which is what a missing login id used to do.
    await expect(page, `${email} lands on its own screen`).toHaveURL(
      email.includes('applicant') ? /applicant\/applied_jobs/ : /employer\/all_jobs/,
    );

    await page.goto('front/logout');
  }
});
