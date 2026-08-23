// @ts-check
/**
 * Nothing behind a login opens without one.
 *
 * Every screen in the two portals - My Stores, Post New Shift, Edit Profile,
 * Change Password, the applicant's Applied Shifts, the ajax endpoints the forms
 * call - is guarded by the `setup()` call at the top of its controller method.
 * That is the only thing standing between a signed-out stranger and the page,
 * and auto-routing publishes a URL for every public method whether or not
 * `Routes.php` names one. A method added without that one line is a page with
 * no lock, and nothing about it looks wrong in review.
 *
 * So the list of URLs here is **not written down**. It is read out of
 * `Employer.php` and `Applicant.php` at run time, which means a method added
 * next month is covered by this file the day it is written, and the test fails
 * the first time somebody forgets. A hard-coded list would have gone stale on
 * the same afternoon.
 *
 * Two questions per URL, because they fail differently:
 *
 *   - signed out entirely -> the login page
 *   - signed in as the other kind of account -> the login page as well, so an
 *     applicant cannot open an employer's stores by typing the address
 */
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { loginAsFrontUser } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';
const PREFIX = 'e2e.guard.';

const OWNER = { user: `${PREFIX}owner@example.com`, pass: PASSWORD };
const APPLICANT = { user: `${PREFIX}applicant@example.com`, pass: PASSWORD };

/**
 * The public methods of a controller, which auto-routing turns into URLs.
 *
 * `protected` and `private` ones are skipped by the pattern itself - they carry
 * a keyword between the indent and `function`, and no URL.
 *
 * @param {string} controller e.g. 'Employer'
 * @returns {string[]}
 */
function publicMethods(controller) {
  const file = path.join(__dirname, '../../../app/Controllers/', `${controller}.php`);
  const src = fs.readFileSync(file, 'utf8');
  const names = [...src.matchAll(/^ {4}(?:public\s+)?function\s+(\w+)\s*\(/gm)].map((m) => m[1]);

  expect(names.length, `${controller}.php should have public methods to check`).toBeGreaterThan(3);

  return names;
}

const EMPLOYER = publicMethods('Employer').map((m) => `employer/${m}`);
const APPLICANT_URLS = publicMethods('Applicant').map((m) => `applicant/${m}`);

/** Both portals, plus the bare controller names that land on `index`. */
const GUARDED = ['employer', 'applicant', ...EMPLOYER, ...APPLICANT_URLS];

function removeFixtures() {
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

/**
 * @param {{user: string, pass: string}} account
 * @param {number} type 1 employer, 2 applicant
 */
function seedUser(account, type) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_userid, u_fname, u_lname, u_pass, u_comp_name,
       u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (${type}, 0, ${type === 1 ? 1 : 0}, '${account.user}', 'Guard', 'Tester',
       MD5('${account.pass}'), 'E2E Guard Co', 0, 'E2E-GUARD', '', '',
       (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       '1 Guard Street', 'M5A 1A1', '4160000850', '${account.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${account.user}';`));
}

test.beforeAll(() => {
  removeFixtures();
  seedUser(OWNER, 1);
  seedUser(APPLICANT, 2);
});

test.afterAll(removeFixtures);

/**
 * Was this request turned away at the door?
 *
 * @param {import('@playwright/test').APIResponse} response
 */
function sentToLogin(response) {
  const location = response.headers()['location'] ?? '';

  return response.status() === 302 && location.includes('front/login');
}

test('every portal URL is refused to a signed-out visitor', async ({ request }) => {
  /** @type {string[]} */
  const open = [];

  for (const url of GUARDED) {
    const response = await request.get(url, { maxRedirects: 0, failOnStatusCode: false });

    if (!sentToLogin(response)) {
      open.push(`${url} -> ${response.status()} ${response.headers()['location'] ?? ''}`);
    }
  }

  expect(open, 'these opened without a login').toEqual([]);

  // A guard that turns everything away because the routes are all 404 would
  // pass the loop above and prove nothing.
  expect(GUARDED.length, 'the URL list was actually built').toBeGreaterThan(15);
});

test('an applicant cannot open the employer screens by typing the address', async ({ page }) => {
  await loginAsFrontUser(page, APPLICANT);
  await page.waitForURL(/applicant/);

  /** @type {string[]} */
  const open = [];

  for (const url of EMPLOYER) {
    // Not logout: it would end the session this test is using, and every URL
    // after it would pass for the wrong reason.
    if (url.endsWith('/logout')) {
      continue;
    }

    const response = await page.request.get(url, { maxRedirects: 0, failOnStatusCode: false });

    if (!sentToLogin(response)) {
      open.push(`${url} -> ${response.status()}`);
    }
  }

  expect(open, 'a pharmacist reached these employer screens').toEqual([]);
});

test('an employer cannot open the applicant screens by typing the address', async ({ page }) => {
  await loginAsFrontUser(page, OWNER);
  await page.waitForURL(/employer/);

  /** @type {string[]} */
  const open = [];

  for (const url of APPLICANT_URLS) {
    if (url.endsWith('/logout')) {
      continue;
    }

    const response = await page.request.get(url, { maxRedirects: 0, failOnStatusCode: false });

    if (!sentToLogin(response)) {
      open.push(`${url} -> ${response.status()}`);
    }
  }

  expect(open, 'an employer reached these applicant screens').toEqual([]);
});

test('the back office is refused to a signed-out visitor and to a signed-in employer', async ({ page, request }) => {
  const screens = ['sadmin/dashboard', 'sadmin/employer', 'sadmin/applicant', 'sadmin/postjobs', 'sadmin/settings'];

  for (const url of screens) {
    const anonymous = await request.get(url, { maxRedirects: 0, failOnStatusCode: false });

    expect(anonymous.status(), `${url} signed out`).toBe(302);
    expect(anonymous.headers()['location'] ?? '', `${url} signed out`).toContain('sadmin/login');
  }

  // An employer's session is not an administrator's, however it was obtained.
  await loginAsFrontUser(page, OWNER);
  await page.waitForURL(/employer/);

  for (const url of screens) {
    const response = await page.request.get(url, { maxRedirects: 0, failOnStatusCode: false });

    expect(response.status(), `${url} as an employer`).toBe(302);
    expect(response.headers()['location'] ?? '', `${url} as an employer`).toContain('sadmin/login');
  }
});

test('a deactivated account cannot keep using the session it already had', async ({ page }) => {
  await loginAsFrontUser(page, OWNER);
  await page.waitForURL(/employer/);

  // Signed in and working, then switched off in the back office.
  query(`UPDATE users SET u_status = 0 WHERE u_userid = '${OWNER.user}';`);

  const response = await page.request.get('employer/all_jobs', { maxRedirects: 0, failOnStatusCode: false });

  query(`UPDATE users SET u_status = 1 WHERE u_userid = '${OWNER.user}';`);

  expect(
    sentToLogin(response),
    'a session outlived the account it belongs to - deactivating somebody does not remove them until they sign out',
  ).toBe(true);
});
