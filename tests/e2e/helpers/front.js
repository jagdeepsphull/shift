// @ts-check
/**
 * Fixtures and logins for the two front-end accounts: an agency (u_usertype 1)
 * and a pharmacist (u_usertype 2).
 *
 * The shift dates are seeded deliberately out of order - and out of order in a
 * way that a text sort gets wrong - so a list that is merely sorted by the
 * `p_dates` string, or by the record number, fails the assertion.
 */
const { expect } = require('@playwright/test');
const { query, scalar } = require('./db');
const { readCaptchaCode } = require('./session');

const AGENCY = {
  user: process.env.E2E_AGENCY_USER || 'e2e.agency@example.com',
  pass: process.env.E2E_AGENCY_PASS || 'E2eTest@12345',
};

const APPLICANT = {
  user: process.env.E2E_APPLICANT_USER || 'e2e.pharmacist@example.com',
  pass: process.env.E2E_APPLICANT_PASS || 'E2eTest@12345',
};

/**
 * The seeded shifts, worst case first.
 *
 * Sorted by the `dd-mm-yyyy` text these read 03-03-2027, 09-11-2026,
 * 21-01-2027 - so a string sort, and the record order they are inserted in,
 * both differ from the chronological answer below.
 */
const SHIFTS = [
  { title: 'E2E-SHIFT-C', dates: '03-03-2027', iso: '2027-03-03' },
  { title: 'E2E-SHIFT-A', dates: '09-11-2026', iso: '2026-11-09' },
  { title: 'E2E-SHIFT-B', dates: '21-01-2027', iso: '2027-01-21' },
];

/** Chronological order: the answer every list under test must produce. */
const EXPECTED = ['E2E-SHIFT-A', 'E2E-SHIFT-B', 'E2E-SHIFT-C'];

function seedUser(account, usertype, firstName, address = '') {
  query(`DELETE FROM users WHERE u_userid = '${account.user}';`);

  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_userid, u_fname, u_lname, u_pass, u_comp_name,
       u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress,
       reset_token, token_expiry)
    VALUES
      (${usertype}, 0, '${account.user}', '${firstName}', 'E2E', MD5('${account.pass}'), 'E2E Pharmacy',
       0, 'E2E-LIC', '', '', 0, 0,
       '${address}', '', '0000000000', '${account.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1',
       '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${account.user}';`));
}

/**
 * Seed the agency, the pharmacist, three shifts and three applications.
 *
 * @returns {{agencyId: number, applicantId: number}}
 */
function seedShiftFixture() {
  removeShiftFixture();

  // The agency's login address stands in for the store address on shifts from
  // before the multi-store feature (p_store_id 0), so seed a recognisable one.
  const agencyId = seedUser(AGENCY, 1, 'Agency', '12 Fixture Lane');
  const applicantId = seedUser(APPLICANT, 2, 'Pharmacist');

  const province = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;');
  const city = scalar('SELECT c_id FROM city WHERE c_status = 1 LIMIT 1;');
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  for (const shift of SHIFTS) {
    query(`
      INSERT INTO post_job
        (u_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
         p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
         p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved,
         created, modified)
      VALUES
        (${agencyId}, 'E2E Pharmacy', '${shift.title}', 0, ${province}, ${city}, ${shiftFor},
         30, 30, '${shift.dates}', '${shift.iso}', '09:00 - 17:00',
         '', '', 'Seeded by the end-to-end suite.', 0, 1, 1,
         NOW(), NOW());
    `);

    const pid = scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${shift.title}';`);

    query(`
      INSERT INTO stu_saved_applied_jobs
        (u_id, agency_id, p_id, sj_applied_date, sj_status, sj_is_approved,
         sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments,
         created, modified)
      VALUES
        (${applicantId}, ${agencyId}, ${pid}, NOW(), 1, 0,
         '', '', '', 0,
         NOW(), NOW());
    `);
  }

  return { agencyId, applicantId };
}

/** Remove everything seedShiftFixture created. Safe to call when nothing exists. */
function removeShiftFixture() {
  query(`
    DELETE FROM stu_saved_applied_jobs
     WHERE p_id IN (SELECT p_id FROM (SELECT p_id FROM post_job WHERE p_job_title LIKE 'E2E-SHIFT-%') x);
  `);
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-SHIFT-%';");
  query(`DELETE FROM users WHERE u_userid IN ('${AGENCY.user}', '${APPLICANT.user}');`);
}

/**
 * Log in through the public form, which shows the same verification image as
 * the admin one.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{user: string, pass: string}} account
 */
async function loginAsFrontUser(page, account) {
  const captchaLoaded = page.waitForResponse(
    (r) => r.url().includes('/front/test_cap') && r.status() === 200,
  );
  await page.goto('front/login');
  await captchaLoaded;

  // The page asks for a verification image for the login tab and again for the
  // register tab beside it, and the session keeps whichever answered last. Wait
  // for the page to go quiet before reading, or the code posted below is the
  // one the first request wrote and the login is refused - which then reads as
  // an unrelated failure on whatever screen the test went on to open.
  await page.waitForLoadState('networkidle').catch(() => {
    /* a still-open long poll should not fail the test */
  });

  const code = await readCaptchaCode(page.context());
  expect(code, 'verification code should be in the session').toBeTruthy();

  // The register form on the same page repeats these field names, so stay
  // inside the login form.
  const form = page.locator('#login-form');

  await form.locator('input[name="username"]').fill(account.user);
  await form.locator('input[name="password"]').fill(account.pass);
  await form.locator('input[name="captcha"]').fill(String(code));

  await Promise.all([
    page.waitForLoadState('load'),
    form.locator('[name="loginSubmit"]').click(),
  ]);
}

const loginAsAgency = (page) => loginAsFrontUser(page, AGENCY);
const loginAsApplicant = (page) => loginAsFrontUser(page, APPLICANT);

module.exports = {
  AGENCY,
  APPLICANT,
  SHIFTS,
  EXPECTED,
  seedShiftFixture,
  removeShiftFixture,
  loginAsFrontUser,
  loginAsAgency,
  loginAsApplicant,
};
