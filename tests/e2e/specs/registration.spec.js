// @ts-check
/**
 * Public registration, once for each of the four account types - change
 * request B4.
 *
 * employer-multi-store.spec.js already checks that the form *asks* each type
 * for the right fields. This file goes the rest of the way: it fills the form
 * in, submits it, and reads back what the account actually became -
 *
 *   manager          -> u_usertype 1, u_emp_role 2, parent = the chosen group,
 *                       plus a store row for the location it was given
 *   owner_multi      -> u_usertype 1, u_emp_role 1, no parent, no store row
 *                       (their stores are added afterwards)
 *   owner_individual -> u_usertype 1, u_emp_role 2, no parent, one store row
 *   applicant        -> u_usertype 2, u_emp_role 0, an applicant sub-type
 *
 * and then that the new account is held for approval and, once approved, logs
 * in and lands on the screen its type belongs on.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { readCaptchaCode } = require('../helpers/session');
const { query, scalar } = require('../helpers/db');
const { GROUP, seedPharmacyGroup, removePharmacyGroup } = require('../helpers/stores');

/** Every fixture account shares this prefix so cleanup can find them all. */
const PREFIX = 'e2e.reg.';

const PASSWORD = 'E2eTest@12345';

/**
 * The four types, and what each should turn into.
 *
 * `store` is whether registering should also create a row in `store`: a
 * location was described on the form, so the account starts with one. A
 * multi-store owner describes a person, and an applicant is not an employer at
 * all, so neither gets one.
 */
const TYPES = [
  {
    key: 'manager',
    label: 'Manager',
    email: `${PREFIX}manager@example.com`,
    company: 'E2E Managed Store',
    usertype: 1,
    empRole: 2,
    hasGroup: true,
    asksLocation: true,
    store: true,
  },
  {
    key: 'owner_multi',
    label: 'Owner (Multi Store)',
    email: `${PREFIX}ownermulti@example.com`,
    company: 'E2E Multi Pharmacy',
    usertype: 1,
    empRole: 1,
    hasGroup: false,
    asksLocation: false,
    store: false,
  },
  {
    key: 'owner_individual',
    label: 'Owner (Individual Store)',
    email: `${PREFIX}ownersingle@example.com`,
    company: 'E2E Single Store',
    usertype: 1,
    empRole: 2,
    hasGroup: false,
    asksLocation: true,
    store: true,
  },
  {
    key: 'applicant',
    label: 'Applicant',
    email: `${PREFIX}applicant@example.com`,
    company: '',
    usertype: 2,
    empRole: 0,
    hasGroup: false,
    asksLocation: true,
    store: false,
  },
];

const ADDRESS = '77 Registration Way, Unit 3';
const POSTCODE = 'M5A 1A1';
const PHONE = '4160000777';

/** @type {number} */
let groupId;

/** Remove every account this file registers, and anything hanging off it. */
function removeFixtures() {
  query(`
    DELETE FROM store WHERE u_id IN (
      SELECT u_id FROM (SELECT u_id FROM users WHERE u_userid LIKE '${PREFIX}%') x);
  `);
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

test.beforeAll(() => {
  removeFixtures();
  // A manager must pick the pharmacy group they answer to, so there has to be
  // an approved multi-store owner for the dropdown to offer.
  groupId = seedPharmacyGroup();
});

test.afterAll(() => {
  removeFixtures();
  removePharmacyGroup();
});

/**
 * Open the registration form and hand back the verification code the session
 * is holding for it.
 *
 * The page requests the captcha image for both the login and the register tab;
 * waiting for the page to go quiet first means the code read here is the one
 * the session ended up with, not whichever request happened to finish first.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string>}
 */
async function openRegistration(page) {
  await page.goto('front/register');
  await settle(page);

  const code = await readCaptchaCode(page.context());
  expect(code, 'verification code should be in the session').toBeTruthy();

  return String(code);
}

/**
 * Fill the registration form as one account type and submit it.
 *
 * Only the fields the chosen type is actually asked for are touched - filling
 * a hidden one would test something the person registering never sees.
 *
 * @param {import('@playwright/test').Page} page
 * @param {typeof TYPES[number]} type
 * @param {{email?: string, captcha?: string}} [overrides]
 */
async function register(page, type, overrides = {}) {
  const code = overrides.captcha ?? (await openRegistration(page));
  const form = page.locator('#register-form');

  await page.selectOption('#usrtpe', type.key);

  if (type.usertype === 1) {
    await page.fill('#u_comp_name', type.company);
  } else {
    // An applicant says what kind of applicant they are instead.
    await page.selectOption('#u_usersubtype', { index: 1 });
  }

  if (type.hasGroup) {
    await page.selectOption('#u_parent_id', String(groupId));
  }

  await page.fill('#u_fname', 'Reg');
  await page.fill('#u_lname', type.label.replace(/[^A-Za-z ]/g, '').trim() || 'Tester');
  await page.fill('#u_email', overrides.email ?? type.email);
  await page.fill('#u_phone', PHONE);

  if (type.asksLocation) {
    await page.selectOption('#u_l_provice', { index: 1 });
    await page.fill('#u_licence_no', 'E2E-REG-1');
    await form.locator('textarea[name="u_address1"]').fill(ADDRESS);

    // The city list is fetched for the chosen province, so wait for it to
    // arrive rather than selecting from an empty dropdown.
    const cities = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));
    await page.selectOption('#provincelist', { index: 1 });
    await cities;
    await expect(page.locator('#city option')).not.toHaveCount(1);
    await page.selectOption('#city', { index: 1 });

    await form.locator('input[name="u_pincode"]').fill(POSTCODE);
  }

  await page.fill('#mainpassword', PASSWORD);
  await page.fill('#conf_password', PASSWORD);

  // Both tabs carry a field with id "captcha", so stay inside this form.
  await form.locator('input[name="captcha"]').fill(code);

  await Promise.all([
    page.waitForLoadState('load'),
    form.locator('[name="signupSubmit"]').click(),
  ]);
  await settle(page);
}

/**
 * The registered account as a plain object, or null when nothing was created.
 *
 * @param {string} email
 * @returns {{id: number, usertype: number, subtype: number, empRole: number,
 *            parentId: number, company: string, licence: string, province: number,
 *            city: number, address: string, pincode: string, status: number,
 *            created: string, pass: string}|null}
 */
function account(email) {
  const row = scalar(`
    SELECT CONCAT_WS('|', u_id, u_usertype, u_usersubtype, u_emp_role, u_parent_id,
                     u_comp_name, u_licence_no, u_provice, u_city, u_address1,
                     u_pincode, u_status, IFNULL(created, ''), u_pass)
      FROM users WHERE u_userid = '${email}';
  `);

  if (row === '') return null;

  const [id, usertype, subtype, empRole, parentId, company, licence, province, city,
    address, pincode, status, created, pass] = row.split('|');

  return {
    id: Number(id),
    usertype: Number(usertype),
    subtype: Number(subtype),
    empRole: Number(empRole),
    parentId: Number(parentId),
    company,
    licence,
    province: Number(province),
    city: Number(city),
    address,
    pincode,
    status: Number(status),
    created,
    pass,
  };
}

for (const type of TYPES) {
  test(`${type.label} can register, and the account is created as one`, async ({ page }) => {
    await register(page, type);
    await expectNoServerError(page);

    // A rejected form comes back on the register tab with the reason on it;
    // reporting that beats an unexplained "no row was created".
    const flash = (await page.locator('.alert').first().textContent().catch(() => '')) || '';
    expect(flash, `registration was refused: ${flash.trim()}`).toContain('Registration successful');
    await expect(page).toHaveURL(/front\/login/);

    const user = account(type.email);
    expect(user, 'the account was created').not.toBeNull();
    if (user === null) return; // narrows the type for everything below

    expect(user.usertype, 'account side (1 employer / 2 applicant)').toBe(type.usertype);
    expect(user.empRole, 'store role').toBe(type.empRole);
    expect(user.parentId, 'pharmacy group').toBe(type.hasGroup ? groupId : 0);
    expect(user.company, 'store or pharmacy name').toBe(type.company);

    // Held for approval, and counted as a sign-up of this month.
    expect(user.status, 'a new account waits for the administrator').toBe(0);
    expect(user.created, 'the sign-up date is recorded').not.toBe('');

    // The password is stored as a modern hash, not as the MD5 digest the
    // legacy rows carry.
    expect(user.pass, 'the password is not stored in the clear').not.toBe(PASSWORD);
    expect(user.pass, 'nor as an MD5 digest').toMatch(/^\$2y\$|^\$argon2/);

    if (type.usertype === 2) {
      expect(user.subtype, 'the applicant type that was chosen').toBeGreaterThan(0);
    } else {
      expect(user.subtype, 'an employer has no applicant type').toBe(0);
    }

    if (type.asksLocation) {
      expect(user.address).toBe(ADDRESS);
      expect(user.pincode).toBe(POSTCODE);
      expect(user.licence).toBe('E2E-REG-1');
      expect(user.province, 'province').toBeGreaterThan(0);
      expect(user.city, 'city').toBeGreaterThan(0);
    } else {
      // A multi-store owner was never asked for a location, so nothing was
      // invented for them - their stores carry it instead.
      expect(user.address, 'no address is stored for a multi-store owner').toBe('');
      expect(user.licence).toBe('');
      expect(user.province).toBe(0);
      expect(user.city).toBe(0);
    }

    // The store the account starts with, if its type describes one.
    const stores = Number(scalar(`SELECT COUNT(*) FROM store WHERE u_id = ${user.id};`) || 0);
    expect(stores, type.store ? 'the location becomes a store' : 'no store yet').toBe(
      type.store ? 1 : 0,
    );

    if (type.store) {
      const store = scalar(`
        SELECT CONCAT_WS('|', s_name, s_number, s_address, s_pincode, s_phone, s_status)
          FROM store WHERE u_id = ${user.id};
      `).split('|');

      expect(store[0], 'the store is named after the one that was registered').toBe(type.company);
      expect(store[1]).toBe('E2E-REG-1');
      expect(store[2]).toBe(ADDRESS);
      expect(store[3]).toBe(POSTCODE);
      expect(store[4]).toBe(PHONE);
      expect(store[5], 'the store is usable as soon as the account is approved').toBe('1');
    }
  });
}

test('a new account cannot log in until the administrator approves it', async ({ page }) => {
  const user = account(TYPES[0].email);
  test.skip(user === null, 'the manager registration test did not create an account');
  if (user === null) return;

  expect(user.status, 'still pending at this point').toBe(0);

  await page.goto('front/login');
  await settle(page);

  const code = await readCaptchaCode(page.context());
  const form = page.locator('#login-form');

  await form.locator('input[name="username"]').fill(TYPES[0].email);
  await form.locator('input[name="password"]').fill(PASSWORD);
  await form.locator('input[name="captcha"]').fill(String(code));

  await Promise.all([
    page.waitForLoadState('load'),
    form.locator('[name="loginSubmit"]').click(),
  ]);
  await settle(page);

  await expect(page.locator('.alert')).toContainText('not active');
  await expect(page, 'and it stays on the login page').toHaveURL(/front\/login/);
  await expectNoServerError(page);
});

for (const type of TYPES) {
  test(`an approved ${type.label} logs in with the password they chose`, async ({ page }) => {
    const user = account(type.email);
    test.skip(user === null, `the ${type.label} registration test did not create an account`);
    if (user === null) return;

    query(`UPDATE users SET u_status = 1 WHERE u_id = ${user.id};`);

    await page.goto('front/login');
    await settle(page);

    const code = await readCaptchaCode(page.context());
    const form = page.locator('#login-form');

    await form.locator('input[name="username"]').fill(type.email);
    await form.locator('input[name="password"]').fill(PASSWORD);
    await form.locator('input[name="captcha"]').fill(String(code));

    await Promise.all([
      page.waitForLoadState('load'),
      form.locator('[name="loginSubmit"]').click(),
    ]);
    await settle(page);

    // Each side of the site has its own home: employers land on their shifts,
    // applicants on the ones they applied for.
    await expect(page, `a ${type.label} lands on their own screen`).toHaveURL(
      type.usertype === 1 ? /employer\/all_jobs/ : /applicant\/applied_jobs/,
    );
    await expectNoServerError(page);

    await page.goto('front/logout');
  });
}

test('the same email cannot be registered twice', async ({ page }) => {
  const taken = TYPES[3]; // the applicant registered above

  await register(page, taken);
  await expectNoServerError(page);

  await expect(page.locator('.alert')).toContainText(/already/i);
  await expect(page, 'the form comes back rather than moving on').toHaveURL(/front\/register/);

  // and nothing was added under that address
  expect(
    Number(scalar(`SELECT COUNT(*) FROM users WHERE u_userid = '${taken.email}';`) || 0),
    'still exactly one account for that email',
  ).toBe(1);
});

test('a wrong verification code refuses the registration', async ({ page }) => {
  const type = TYPES[2]; // owner_individual, a form that fills in completely
  const email = `${PREFIX}captcha@example.com`;

  await openRegistration(page);
  await register(page, { ...type, email }, { email, captcha: '000000' });

  await expect(page.locator('.alert')).toContainText(/CAPTCHA/i);
  expect(
    Number(scalar(`SELECT COUNT(*) FROM users WHERE u_userid = '${email}';`) || 0),
    'no account is created when the code is wrong',
  ).toBe(0);
  await expectNoServerError(page);
});
