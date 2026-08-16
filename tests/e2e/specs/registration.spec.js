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
 *                       and u_store_id = one of that group's existing stores.
 *                       No store row is created: they join a location rather
 *                       than describing one, and its name, number and address
 *                       are copied onto the login
 *   owner            -> u_usertype 1, u_emp_role 1, no parent, no store row
 *                       (their stores are added afterwards)
 *   applicant        -> u_usertype 2, u_emp_role 0, an applicant sub-type
 *
 * and then that the new account is held for approval and, once approved, logs
 * in and lands on the screen its type belongs on.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { readCaptchaCode } = require('../helpers/session');
const { query, scalar } = require('../helpers/db');
const {
  GROUP,
  GROUP_STORES,
  seedPharmacyGroup,
  removePharmacyGroup,
  seedGroupStores,
  removeGroupStores,
  REG_STORE_SELECT,
  REG_STORE_ENDPOINT,
  multiStoreMissing,
} = require('../helpers/stores');

/** Every fixture account shares this prefix so cleanup can find them all. */
const PREFIX = 'e2e.reg.';

const PASSWORD = 'E2eTest@12345';

/**
 * The three types, and what each should turn into.
 *
 * `key` is the value the dropdown posts, which is the number the database
 * stores - `registerTypes` in config.
 *
 * `store` is whether registering should also create a row in `store`: a
 * location was described on the form, so the account starts with one. A
 * multi-store owner describes a person, and an applicant is not an employer at
 * all, so neither gets one. Nor does a manager - `picksStore` says they choose
 * one of their group's instead, which is a different thing entirely.
 */
const TYPES = [
  {
    key: '2',
    label: 'Manager',
    email: `${PREFIX}manager@example.com`,
    // Not typed - copied off the store they pick, which is why it has to be
    // the seeded store's own name.
    company: GROUP_STORES[0].name,
    usertype: 1,
    empRole: 2,
    hasGroup: true,
    picksStore: true,
    asksLocation: false,
    store: false,
  },
  {
    key: '1',
    label: 'Owner',
    email: `${PREFIX}owner@example.com`,
    company: 'E2E Multi Pharmacy',
    usertype: 1,
    empRole: 1,
    hasGroup: false,
    asksLocation: false,
    store: false,
  },
  {
    key: '3',
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

/** @type {Array<{name: string, number: string, address: string, phone: string, id: number}>} */
let groupStores = [];

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
  // A manager must pick the corporate group they answer to, so there has to be
  // an approved multi-store owner for the dropdown to offer - and it has to
  // own a store, or the group is not offered at all and the manager has
  // nowhere to work.
  groupId = seedPharmacyGroup();
  groupStores = seedGroupStores(groupId);
});

test.afterAll(() => {
  removeFixtures();
  // Before the group: removePharmacyGroup deletes the owner row and would
  // leave these orphaned, and nothing else sweeps them up.
  removeGroupStores();
  removePharmacyGroup();
});

// The manager flow needs the store column; without it the form is the old one.
test.beforeEach(() => {
  const missing = multiStoreMissing();
  test.skip(missing !== null, missing || '');
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

  if (type.usertype === 1 && !type.picksStore) {
    await page.fill('#u_comp_name', type.company);
  } else if (type.usertype === 2) {
    // An applicant says what kind of applicant they are instead.
    await page.selectOption('#u_usersubtype', { index: 1 });
  }

  if (type.hasGroup) {
    // Choosing the group is what fetches its stores, the same way a province
    // fetches its cities. The watcher has to be armed before the choice, and
    // the choice made only once: selecting the value it already holds fires no
    // change event, so a second select would wait for a request never sent.
    const stores = type.picksStore
      ? page.waitForResponse((r) => r.url().includes(REG_STORE_ENDPOINT))
      : null;

    await page.selectOption('#u_parent_id', String(groupId));

    if (stores !== null) {
      await stores;
      await expect(page.locator(`${REG_STORE_SELECT} option`)).not.toHaveCount(1);
      await page.selectOption(REG_STORE_SELECT, String(groupStores[0].id));
    }
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

  // Both tabs carry a field named "captcha", so stay inside this form.
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
 *            parentId: number, storeId: number, company: string, licence: string,
 *            province: number, city: number, address: string, pincode: string,
 *            status: number, created: string, pass: string}|null}
 */
function account(email) {
  const row = scalar(`
    SELECT CONCAT_WS('|', u_id, u_usertype, u_usersubtype, u_emp_role, u_parent_id,
                     IFNULL(u_store_id, 0),
                     u_comp_name, u_licence_no, u_provice, u_city, u_address1,
                     u_pincode, u_status, IFNULL(created, ''), u_pass)
      FROM users WHERE u_userid = '${email}';
  `);

  if (row === '') return null;

  const [id, usertype, subtype, empRole, parentId, storeId, company, licence, province, city,
    address, pincode, status, created, pass] = row.split('|');

  return {
    id: Number(id),
    usertype: Number(usertype),
    subtype: Number(subtype),
    empRole: Number(empRole),
    parentId: Number(parentId),
    storeId: Number(storeId),
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
    expect(user.company, 'store or corporate group name').toBe(type.company);

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

    if (type.picksStore) {
      // A manager typed no address at all. Everything here was copied off the
      // store they chose, so the screens that read these columns off the login
      // rather than through the store keep working.
      const chosen = groupStores[0];

      expect(user.storeId, 'the store that was chosen').toBe(chosen.id);
      expect(user.address, "the store's address, copied").toBe(chosen.address);
      expect(user.licence, "the store's number, copied").toBe(chosen.number);
      expect(user.pincode).toBe(POSTCODE);
      expect(user.province, 'province').toBeGreaterThan(0);
      expect(user.city, 'city').toBeGreaterThan(0);
    } else if (type.asksLocation) {
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

    // Only a manager answers to a store somebody else owns.
    if (!type.picksStore) {
      expect(user.storeId, 'an owner owns its stores outright').toBe(0);
    }

    // The store the account starts with, if its type describes one.
    const stores = Number(scalar(`SELECT COUNT(*) FROM store WHERE u_id = ${user.id};`) || 0);
    expect(stores, type.store ? 'the location becomes a store' : 'no store yet').toBe(
      type.store ? 1 : 0,
    );

    if (type.picksStore) {
      // The point of the change: joining a store must not duplicate it, and
      // the row stays the group's rather than moving to the manager.
      expect(
        scalar(`SELECT u_id FROM store WHERE s_id = ${user.storeId};`),
        'the store still belongs to the corporate group',
      ).toBe(String(groupId));
    }

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

test('a manager cannot claim a store belonging to another group', async ({ page }) => {
  // The dropdown only ever offers the chosen group's stores, so this is what a
  // hand-edited form looks like: a real, active store id that belongs to
  // somebody else. The server has to check the pair rather than trust the post,
  // because the AJAX endpoint's guard says nothing about the save.
  const email = `${PREFIX}poacher@example.com`;

  const otherOwnerId = Number(
    scalar(`SELECT u_id FROM users WHERE u_usertype = 1 AND u_id <> ${groupId} LIMIT 1;`) || 0,
  );
  test.skip(otherOwnerId === 0, 'no second employer to own a rival store');

  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${otherOwnerId}, 'E2E Rival Store', 'E2E-R01', 0, 0, '1 Rival Road', 'M5A 1A1', '4160000999', 1);
  `);

  const rivalId = Number(scalar("SELECT MAX(s_id) FROM store WHERE s_name = 'E2E Rival Store';"));

  try {
    const code = await openRegistration(page);
    const form = page.locator('#register-form');

    await page.selectOption('#usrtpe', '2');

    const stores = page.waitForResponse((r) => r.url().includes(REG_STORE_ENDPOINT));
    await page.selectOption('#u_parent_id', String(groupId));
    await stores;

    // The rival is not on the list, so put it there the way a tampered form
    // would, then choose it.
    await page.locator(REG_STORE_SELECT).evaluate((el, id) => {
      const option = document.createElement('option');
      option.value = String(id);
      option.textContent = 'Rival';
      el.appendChild(option);
      /** @type {HTMLSelectElement} */ (el).value = String(id);
    }, rivalId);

    await page.fill('#u_fname', 'Reg');
    await page.fill('#u_lname', 'Poacher');
    await page.fill('#u_email', email);
    await page.fill('#u_phone', PHONE);
    await page.fill('#mainpassword', PASSWORD);
    await page.fill('#conf_password', PASSWORD);
    await form.locator('input[name="captcha"]').fill(code);

    await Promise.all([
      page.waitForLoadState('load'),
      form.locator('[name="signupSubmit"]').click(),
    ]);
    await settle(page);

    await expect(page.locator('.alert-danger')).toContainText(/corporate group's stores/i);
    expect(account(email), 'no account is created from a store that is not the group\'s').toBeNull();
    await expectNoServerError(page);
  } finally {
    query("DELETE FROM store WHERE s_name = 'E2E Rival Store';");
  }
});

test('a store that already has a manager will not take a second one', async ({ page }) => {
  // One store, one manager. The sitting one here is still waiting for the
  // administrator, which is the case that matters: if a pending registration
  // did not hold the store, two people could claim the same branch the same
  // afternoon and nothing would notice until both were approved.
  const sitting = `${PREFIX}sitting@example.com`;
  const second = `${PREFIX}second@example.com`;
  const claimed = groupStores[1];

  // A branch of the same group with nobody on it, so the test can tell "this
  // store is taken" apart from "the group is closed". Seeded rather than
  // borrowed from the fixture: by this point in the file the other one has the
  // manager that registered at the top of it.
  query("DELETE FROM store WHERE s_name = 'E2E Group Store Free';");
  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${groupId}, 'E2E Group Store Free', 'E2E-G03', 0, 0, '33 Group Close', '${POSTCODE}', '4160000903', 1);
  `);

  const freeId = Number(scalar("SELECT MAX(s_id) FROM store WHERE s_name = 'E2E Group Store Free';"));

  query(`DELETE FROM users WHERE u_userid = '${sitting}';`);
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid, u_fname, u_lname,
       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, 2, ${groupId}, ${claimed.id}, '${sitting}', 'Sitting', 'Manager',
       MD5('${PASSWORD}'), '${claimed.name}', 0, '${claimed.number}', '', '', 0, 0,
       '${claimed.address}', '${POSTCODE}', '${PHONE}', '${sitting}', 1, 0, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  try {
    const code = await openRegistration(page);
    const form = page.locator('#register-form');

    await page.selectOption('#usrtpe', '2');

    const stores = page.waitForResponse((r) => r.url().includes(REG_STORE_ENDPOINT));
    await page.selectOption('#u_parent_id', String(groupId));
    await stores;

    // The picker says so, rather than leaving the store off the list and the
    // person wondering where their own branch went.
    const option = page.locator(`${REG_STORE_SELECT} option[value="${claimed.id}"]`);
    await expect(option, 'the taken branch is named as taken').toContainText(/already has a manager/i);
    await expect(option, 'and cannot be chosen').toHaveAttribute('disabled', /.*/);

    // Only that branch: the group's free store is still there to be chosen.
    await expect(
      page.locator(`${REG_STORE_SELECT} option[value="${freeId}"]`),
      'the branch with no manager is still open',
    ).not.toHaveAttribute('disabled', /.*/);

    // Choose the taken one anyway - a page that was open before the sitting
    // manager registered, or a hand-edited form, both arrive looking like this.
    await page.locator(REG_STORE_SELECT).evaluate((el, id) => {
      const chosen = el.querySelector(`option[value="${id}"]`);
      if (chosen) chosen.removeAttribute('disabled');
      /** @type {HTMLSelectElement} */ (el).value = String(id);
    }, claimed.id);

    await page.fill('#u_fname', 'Reg');
    await page.fill('#u_lname', 'Second');
    await page.fill('#u_email', second);
    await page.fill('#u_phone', PHONE);
    await page.fill('#mainpassword', PASSWORD);
    await page.fill('#conf_password', PASSWORD);
    await form.locator('input[name="captcha"]').fill(code);

    await Promise.all([
      page.waitForLoadState('load'),
      form.locator('[name="signupSubmit"]').click(),
    ]);
    await settle(page);

    await expect(page.locator('.alert-danger')).toContainText(/already has a manager/i);
    expect(account(second), 'no second account on a store that is taken').toBeNull();

    // And the store still belongs to the one who claimed it first.
    expect(
      Number(scalar(`
        SELECT COUNT(*) FROM users
         WHERE u_store_id = ${claimed.id} AND u_usertype = 1 AND u_emp_role = 2;
      `) || 0),
      'one manager on the branch, not two',
    ).toBe(1);

    await expectNoServerError(page);
  } finally {
    query(`DELETE FROM users WHERE u_userid = '${sitting}';`);
    query("DELETE FROM store WHERE s_name = 'E2E Group Store Free';");
  }
});

test('the same email cannot be registered twice', async ({ page }) => {
  const taken = TYPES[2]; // the applicant registered above

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
  const type = TYPES[2]; // applicant, a form that fills in completely
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
