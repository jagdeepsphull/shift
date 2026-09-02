// @ts-check
/**
 * Where a shift actually is, and how to get there.
 *
 * A postal address is not enough to find a pharmacy inside a supermarket or one
 * of four units in a plaza, so a store carries a location label and a Google
 * Maps link pasted by hand. Both have to reach the two places an applicant
 * reads before turning up: the shift page and the booking e-mail.
 *
 * The booking e-mail is the sharper case. It used to print the employer's login
 * address, which for a multi-store owner is their head office - so the person
 * booked onto a branch shift was sent to the wrong building.
 */
const { test, expect } = require('@playwright/test');
const { settle } = require('../helpers/admin');
const { loginAsFrontUser } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';

const OWNER = 'e2e.maplink.owner@example.com';
const APPLICANT = 'e2e.maplink.applicant@example.com';

const MAP_URL = 'https://maps.app.goo.gl/e2eMapLinkPin';
const LABEL = 'Inside the Superstore, north entrance';
const HEAD_OFFICE = '1 Head Office Plaza';
const BRANCH_ADDRESS = '250 Branch Boulevard';
const BRANCH_PHONE = '4165552222';

/** What the applicant is paid - `p_ac_hourly_rate`, not the employer's rate. */
const RATE = 30;

/**
 * The same rate as the page shows it. `p_ac_hourly_rate` is DECIMAL(6,2), and
 * the shift page stopped trimming the cents off when rates stopped being whole
 * dollars - "30.5" is not how money reads.
 */
const RATE_SHOWN = RATE.toFixed(2);

/** @type {Record<string, number>} */
const ids = {};
let PROVINCE = '0';
let CITY = '0';
let CITY_NAME = '';

function removeSeeded() {
  query("DELETE FROM stu_saved_applied_jobs WHERE p_id IN (SELECT p_id FROM (SELECT p_id FROM post_job WHERE p_job_title LIKE 'E2E-MAPLINK-%') x);");
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-MAPLINK-%';");
  query("DELETE FROM store WHERE s_name LIKE 'E2E Maplink%';");
  query("DELETE FROM users WHERE u_email LIKE 'e2e.maplink.%@example.com';");
}

test.beforeAll(() => {
  removeSeeded();

  PROVINCE = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;') || '0';
  CITY = scalar(`SELECT c_id FROM city WHERE c_status = 1 AND c_province = ${PROVINCE} LIMIT 1;`) || '0';
  // The town is shown at every tier, so the tests need to know its name.
  CITY_NAME = scalar(`SELECT c_name FROM city WHERE c_id = ${CITY};`) || '';

  // A multi-store owner whose login address is a head office, not a shop.
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_website, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, 1, 0, '${OWNER}', 'Map', 'Owner', MD5('${PASSWORD}'),
       'E2E Maplink Chain', ${PROVINCE}, 'HQ-1', '', '', ${PROVINCE}, ${CITY},
       '${HEAD_OFFICE}', 'M5A 0A0', '4165550000', '${OWNER}', 'https://chain.example.com', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);
  ids.owner = Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${OWNER}';`));

  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (2, 0, 0, 0, '${APPLICANT}', 'Map', 'Applicant', MD5('${PASSWORD}'),
       '', ${PROVINCE}, 'LIC-1', '', '', ${PROVINCE}, ${CITY},
       '5 Applicant Way', 'M5A 1A1', '4165551111', '${APPLICANT}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);
  ids.applicant = Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${APPLICANT}';`));

  // The branch the shift is really at, with a pasted pin and a label.
  query(`
    INSERT INTO store
      (u_id, s_name, s_number, s_province, s_city, s_address, s_location_label, s_map_url,
       s_pincode, s_phone, s_website, s_status)
    VALUES
      (${ids.owner}, 'E2E Maplink Branch', 'BR-9', ${PROVINCE}, ${CITY}, '${BRANCH_ADDRESS}',
       '${LABEL}', '${MAP_URL}', 'M5A 2B2', '${BRANCH_PHONE}', 'https://branch.example.com', 1);
  `);
  ids.store = Number(scalar("SELECT s_id FROM store WHERE s_name = 'E2E Maplink Branch';"));

  // A store with an address but no pasted link - the state most stores are in.
  query(`
    INSERT INTO store
      (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES
      (${ids.owner}, 'E2E Maplink Plain', 'BR-8', ${PROVINCE}, ${CITY}, '900 Plain Street',
       'M5A 3C3', '4165553333', 1);
  `);
  ids.plainStore = Number(scalar("SELECT s_id FROM store WHERE s_name = 'E2E Maplink Plain';"));

  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  for (const [title, storeId] of [
    ['E2E-MAPLINK-PIN', ids.store],
    ['E2E-MAPLINK-PLAIN', ids.plainStore],
    // A shift at the same branch that the applicant is never booked onto, for
    // the middle tier: signed in, so the rate shows, but not booked, so the
    // pharmacy behind it still does not.
    ['E2E-MAPLINK-OPEN', ids.store],
  ]) {
    query(`
      INSERT INTO post_job
        (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
         p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
         p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
      VALUES
        (${ids.owner}, ${storeId}, 'E2E Maplink Chain', '${title}', 0,
         ${PROVINCE}, ${CITY}, ${shiftFor}, 45, ${RATE}, '15-09-2027', '2027-09-15', '09:00 - 17:00',
         '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());
    `);
  }

  ids.pinShift = Number(scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-MAPLINK-PIN';"));
  ids.plainShift = Number(scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-MAPLINK-PLAIN';"));
  ids.openShift = Number(scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-MAPLINK-OPEN';"));

  // Book the applicant onto two of the three. `sj_is_approved = 1` is the
  // confirmed booking - the point at which the branch is theirs to know.
  for (const shiftId of [ids.pinShift, ids.plainShift]) {
    query(`
      INSERT INTO stu_saved_applied_jobs
        (u_id, agency_id, p_id, sj_applied_date, sj_status, sj_is_approved,
         sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments,
         created, modified)
      VALUES
        (${ids.applicant}, ${ids.owner}, ${shiftId}, NOW(), 1, 1, '', '', '', 0, NOW(), NOW());
    `);
  }
});

test.afterAll(() => {
  removeSeeded();
});

/**
 * Which pharmacy the shift is at waits for a confirmed booking, so the page
 * assertions below read a shift this applicant is booked onto, as them.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} shiftId
 */
async function openShiftBooked(page, shiftId) {
  await loginAsFrontUser(page, { user: APPLICANT, pass: PASSWORD });
  await page.goto(`front/job_detail/${shiftId}`);
  await settle(page);
}

/**
 * Nothing that names or locates the branch is on the page.
 *
 * A map link is not in itself a leak - a signed-in reader gets one for the town
 * the page does show them - so what is checked is where it points: never the
 * pasted pin, and never the branch's own address.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectPharmacyHidden(page) {
  const body = page.locator('body');

  await expect(body).not.toContainText(BRANCH_ADDRESS);
  await expect(body).not.toContainText(LABEL);
  await expect(body).not.toContainText('E2E Maplink Branch');
  await expect(body).not.toContainText(BRANCH_PHONE);
  await expect(page.locator(`a[href*="maps.app.goo.gl"], a[href*="${encodeURIComponent(BRANCH_ADDRESS)}"]`)).toHaveCount(0);
  await expect(page.locator('a[href="https://branch.example.com"]')).toHaveCount(0);
  await expect(page.locator('a[href="https://chain.example.com"]')).toHaveCount(0);
}

test('a signed-out visitor gets neither the pharmacy nor the rate', async ({ page }) => {
  await page.goto(`front/job_detail/${ids.pinShift}`);
  await settle(page);

  await expectPharmacyHidden(page);

  const body = page.locator('body');

  // The rate waits for an account.
  await expect(body).not.toContainText(`CAD$ ${RATE}`);
  await expect(body).toContainText(/to be disclosed/i);

  // No account, no map: the link is one of the things signing in is for.
  await expect(page.locator('a[href*="google.com/maps"]')).toHaveCount(0);

  // The public outline stays: the town, the date and the way in.
  await expect(body).toContainText(CITY_NAME);
  await expect(body).toContainText('09:00 - 17:00');
  await expect(body).toContainText(/sign in/i);
});

test('signing in reveals the rate but still not the pharmacy', async ({ page }) => {
  // The middle tier, and the one worth pinning: an account is enough to be told
  // what the shift pays, and not enough to be told which branch it is at - or
  // the platform is only a listing that people arrange around.
  await loginAsFrontUser(page, { user: APPLICANT, pass: PASSWORD });
  await page.goto(`front/job_detail/${ids.openShift}`);
  await settle(page);

  await expectPharmacyHidden(page);

  const body = page.locator('body');

  await expect(body).toContainText(`CAD$ ${RATE_SHOWN}/hour`);
  await expect(body).toContainText(CITY_NAME);
  await expect(body).toContainText(/once your booking .* is confirmed/i);

  // The town it does show is a place they can open, on a search for that town
  // and nothing narrower.
  const map = page.locator('a[href*="google.com/maps"]');

  await expect(map).toHaveText(/view on google maps/i);
  await expect(map).toHaveAttribute('href', new RegExp(encodeURIComponent(CITY_NAME)));
});

test('a confirmed booking shows the branch name, address, phone and pasted pin', async ({
  page,
}) => {
  await openShiftBooked(page, ids.pinShift);

  const body = page.locator('body');

  await expect(body).toContainText('E2E Maplink Branch');
  await expect(body).toContainText(BRANCH_ADDRESS);
  await expect(body).toContainText(LABEL);
  await expect(body).toContainText(BRANCH_PHONE);

  // The head office is the employer's login address and has no business here.
  await expect(body).not.toContainText(HEAD_OFFICE);

  // 45 is `p_hourly_rate`, what the employer is billed. It is not the
  // applicant's business and must never reach this page at any tier.
  await expect(body).toContainText(`CAD$ ${RATE_SHOWN}/hour`);
  await expect(body).not.toContainText('CAD$ 45');

  await expect(page.locator(`a[href="${MAP_URL}"]`)).toHaveText(/get directions/i);
});

test('a store with no pasted link still gets directions, via its address', async ({ page }) => {
  await openShiftBooked(page, ids.plainShift);

  const link = page.locator('a[href*="google.com/maps"]');
  await expect(link).toHaveText(/get directions/i);
  await expect(link).toHaveAttribute('href', /900%20Plain%20Street/);
});

test("the store's own website wins over the employer's", async ({ page }) => {
  await openShiftBooked(page, ids.pinShift);

  await expect(page.locator('a[href="https://branch.example.com"]')).toBeVisible();
  await expect(page.locator('a[href="https://chain.example.com"]')).toHaveCount(0);
});

test("a store with no site of its own falls back to the employer's", async ({ page }) => {
  await openShiftBooked(page, ids.plainShift);

  await expect(page.locator('a[href="https://chain.example.com"]')).toBeVisible();
});

test("the applicant's own list carries the branch and its pin", async ({ page }) => {
  // The page somebody actually opens on the morning of the shift. It built its
  // address from the employer's login row, which for a multi-store owner is the
  // head office - the same mistake the booking e-mail used to make.
  await loginAsFrontUser(page, { user: APPLICANT, pass: PASSWORD });
  await page.goto('applicant/applied_jobs');
  await settle(page);

  await page
    .locator('#joblist tbody tr', { hasText: 'E2E-MAPLINK-PIN' })
    .locator('.employer-btn')
    .click();

  await expect(page.locator('#modalAddress')).toHaveValue(new RegExp(BRANCH_ADDRESS));
  await expect(page.locator('#modalAddress')).not.toHaveValue(new RegExp(HEAD_OFFICE));
  await expect(page.locator('#modalName')).toHaveValue('E2E Maplink Branch');

  const map = page.locator('#modalMapLink');

  await expect(map).toBeVisible();
  await expect(map).toHaveAttribute('href', MAP_URL);
});

test('the booking e-mail names the branch, not the head office', async () => {
  // Render the template the booking uses, through the same helper the
  // controller calls. Driving the whole approve journey is another spec's job;
  // what is under test here is which address the message carries.
  const html = renderEmail('booking-applicant', ids.pinShift, ids.applicant);

  expect(html).toContain(BRANCH_ADDRESS);
  expect(html).toContain(LABEL);
  expect(html).toContain(MAP_URL);
  expect(html).toContain('E2E Maplink Branch');

  // The bug this replaces: the owner's login address, which is a head office.
  expect(html).not.toContain(HEAD_OFFICE);
});

test('the day-before reminder names the branch too, not the head office', async () => {
  // The reminder had the same defect as the booking message: it printed the
  // employer's login columns, so the night before the shift the applicant was
  // reminded of an address they were not due at.
  const html = renderEmail('shift-reminder', ids.pinShift, ids.applicant);

  expect(html).toContain(BRANCH_ADDRESS);
  expect(html).toContain(LABEL);
  expect(html).toContain(MAP_URL);
  expect(html).not.toContain(HEAD_OFFICE);
});

test('every lifecycle stage renders a message', async () => {
  // "The format of the e-mail at each stage" is only reviewable if each stage
  // can actually be produced. A template that throws on a missing variable
  // fails here rather than the first time a real event fires it.
  const accountStages = ['welcome', 'account-approved', 'reset-password'];
  const shiftStages = ['shift-posted', 'booking-applicant', 'booking-employer', 'shift-reminder'];

  for (const stage of accountStages) {
    const html = renderEmail(stage, 0);
    expect(html, stage).toContain('</html>');
  }

  for (const stage of shiftStages) {
    const html = renderEmail(stage, ids.pinShift, ids.applicant);
    expect(html, stage).toContain('</html>');
    expect(html, stage).not.toMatch(/Undefined|ErrorException|Whoops/);
  }
});

test('a shift with no store still gets the address it always had', async () => {
  // p_store_id 0 is every shift from before multi-store. Those fall back to the
  // employer's login columns, and must keep doing so.
  query(`UPDATE post_job SET p_store_id = 0 WHERE p_id = ${ids.plainShift};`);

  const html = renderEmail('booking-applicant', ids.plainShift, ids.applicant);
  expect(html).toContain(HEAD_OFFICE);

  query(`UPDATE post_job SET p_store_id = ${ids.plainStore} WHERE p_id = ${ids.plainShift};`);
});

/**
 * Render a lifecycle e-mail for a shift, through the same `email:preview`
 * command a developer uses to eyeball one - so the test reads the message the
 * application would actually send, not a copy of it kept in the test.
 *
 * @param {string} template
 * @param {number} shiftId
 * @param {number} [applicantId]
 * @returns {string}
 */
function renderEmail(template, shiftId, applicantId) {
  const { execFileSync } = require('child_process');
  const path = require('path');

  const args = ['spark', 'email:preview', template, String(shiftId)];
  if (applicantId) args.push(String(applicantId));

  return execFileSync('php', args, {
    cwd: path.resolve(__dirname, '../../..'),
    encoding: 'utf8',
    windowsHide: true,
  });
}
