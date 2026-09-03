// @ts-check
/**
 * What a pharmacist sees on My Shifts when the booking was not theirs to make.
 *
 * The agency books somebody onto a shift from the back office - /sadmin/postjobs
 * add and edit both do it, from the "Book Shift For" picker - and nobody applied
 * for that shift, so the row is written at `sj_status` 6 rather than 1. This
 * screen asked for `sj_status = 1`, so those shifts appeared nowhere in the
 * applicant's account: they were sent the booking e-mail, signed in to check it,
 * and found an empty list.
 *
 * The rows are therefore seeded here in the exact shapes the back office writes
 * - Sadmin::bookApplicant() and Sadmin::cancelBooking() - rather than by driving
 * the admin form, which admin-book-on-add.spec.js and admin-book-on-edit.spec.js
 * already cover from the other side. What is under test is the applicant's half:
 * that each shape reaches their screen, and says the right thing when it gets
 * there.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { seedShiftFixture, removeShiftFixture, loginAsApplicant } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

/** The agency's note on the booking, which the applicant is meant to be able to read. */
const BOOKING_NOTE = 'Agreed on the phone - ask for the manager at the counter.';

/** The fourth shift, invited-to rather than applied for. Cleaned up by the LIKE in the fixture. */
const INVITED = 'E2E-SHIFT-D';

/** @type {{agencyId: number, applicantId: number}} */
let ids;

const shiftId = (title) =>
  Number(scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${title}' LIMIT 1;`));

/**
 * Put the applicant on a shift the way the back office does: no application of
 * their own, a row at status 6, and the shift closed behind it.
 *
 * `approved` is `sj_is_approved` - 1 while they hold it, 2 once the agency has
 * moved the booking to somebody else (cancelBooking() leaves the row at 2 and
 * the status where it was).
 */
function bookOutright(title, approved) {
  const pid = shiftId(title);

  query(`DELETE FROM stu_saved_applied_jobs WHERE p_id = ${pid};`);
  query(`
    INSERT INTO stu_saved_applied_jobs
      (u_id, agency_id, p_id, sj_status, sj_is_approved, sj_applied_date, sj_accept_date,
       sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments,
       created, modified)
    VALUES
      (${ids.applicantId}, ${ids.agencyId}, ${pid}, 6, ${approved}, NOW(), NOW(),
       '${BOOKING_NOTE}', '', '', 0,
       NOW(), NOW());
  `);

  // A booked shift is closed to everybody else - 3 is "Booked".
  query(`UPDATE post_job SET p_approved = 3 WHERE p_id = ${pid};`);

  return pid;
}

/** A shift the employer invited them to and that they have not applied for: status 3. */
function seedInvitation() {
  const province = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;');
  const city = scalar('SELECT c_id FROM city WHERE c_status = 1 LIMIT 1;');
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  query(`
    INSERT INTO post_job
      (u_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
       p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES
      (${ids.agencyId}, 'E2E Pharmacy', '${INVITED}', 0, ${province}, ${city}, ${shiftFor},
       30, 30, '14-12-2026', '2026-12-14', '09:00 - 17:00',
       '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());
  `);

  const pid = shiftId(INVITED);

  query(`
    INSERT INTO stu_saved_applied_jobs
      (u_id, agency_id, p_id, sj_status, sj_is_approved, invite_date,
       sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments,
       created, modified)
    VALUES
      (${ids.applicantId}, ${ids.agencyId}, ${pid}, 3, 0, NOW(),
       '', '', '', 0,
       NOW(), NOW());
  `);

  return pid;
}

test.beforeEach(() => {
  ids = seedShiftFixture();

  // A: booked outright by the agency, and theirs.
  bookOutright('E2E-SHIFT-A', 1);
  // B: left as the fixture wrote it - an application of their own, still pending.
  // C: booked outright and since moved to somebody else.
  bookOutright('E2E-SHIFT-C', 2);
  // D: an invitation, which is not a shift they hold.
  seedInvitation();
});

test.afterAll(() => {
  removeShiftFixture();
});

/**
 * Sign in as the pharmacist and open one of their screens.
 *
 * The public login is raced - the page asks for a verification image twice and
 * the session keeps whichever answered last - so a refused login shows up here
 * as an empty table. Signing in again gets a fresh code, so try twice before
 * believing it. Same reason as applicant-messages.spec.js.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} path
 */
async function openAsApplicant(page, path) {
  for (let attempt = 1; attempt <= 2; attempt += 1) {
    await loginAsApplicant(page);
    await page.goto(path);
    await settle(page);

    if (!page.url().includes('front/login')) break;
  }

  expect(page.url(), `signed in and on ${path}`).toContain(path);
}

/** The row for one seeded shift. */
const rowFor = (page, title) => page.locator('#joblist tbody tr', { hasText: title });

test('a shift the agency booked reaches the applicant, marked Booked', async ({ page }) => {
  await openAsApplicant(page, 'applicant/applied_jobs');

  const row = rowFor(page, 'E2E-SHIFT-A');

  // The regression: no application row of their own, so this was not listed.
  await expect(row, 'the booked shift is on the list').toHaveCount(1);
  await expect(row.locator('.ps-status')).toHaveText('Booked');

  // The date and hours it runs, which is the point of them seeing it at all.
  // As the screen writes it - dateformat(), not the dd-mm-yyyy the column holds.
  await expect(row, 'the shift date is shown').toContainText('09 Nov 2026');
  await expect(row, 'the hours are shown').toContainText('09:00 - 17:00');

  // A booking gets the button that names the branch to turn up at, not the
  // anonymous one an open application gets.
  await expect(row.locator('button.employer-btn')).toHaveCount(1);

  // And the agency's note is readable, from the row the back office wrote.
  const stored = scalar(
    `SELECT sj_admin_comment FROM stu_saved_applied_jobs
      WHERE p_id = ${shiftId('E2E-SHIFT-A')} AND u_id = ${ids.applicantId};`,
  );
  expect(stored, 'the note is on the seeded row').toBe(BOOKING_NOTE);

  await row.getByRole('button', { name: 'Agency Message' }).click();
  await expect(page.locator('.popover-body')).toHaveText(BOOKING_NOTE);

  await expectNoServerError(page);
});

test('an application of their own is still listed beside it', async ({ page }) => {
  await openAsApplicant(page, 'applicant/applied_jobs');

  const row = rowFor(page, 'E2E-SHIFT-B');

  await expect(row, 'the shift they applied for is on the list').toHaveCount(1);
  await expect(row.locator('.ps-status')).toHaveText('Pending');

  // Nothing has been decided, so no branch address is given away yet.
  await expect(row.locator('button.employer-btn')).toHaveCount(0);
  await expect(row.locator('button.shift-btn')).toHaveCount(1);
});

test('a booking moved to somebody else says so rather than vanishing', async ({ page }) => {
  await openAsApplicant(page, 'applicant/applied_jobs');

  const row = rowFor(page, 'E2E-SHIFT-C');

  // The row stays: they were told the shift was theirs, so this screen owes
  // them the correction rather than silence.
  await expect(row, 'the shift they lost is still on the list').toHaveCount(1);
  await expect(row.locator('.ps-status')).toHaveText('Assigned To Someone Else');
});

test('an invitation is not a shift they hold, and is left off', async ({ page }) => {
  await openAsApplicant(page, 'applicant/applied_jobs');

  // The list is applications and bookings - statuses 1 and 6. An invitation
  // (3) is neither, and a row reading "Pending" on a shift they never applied
  // for would say something untrue.
  expect(shiftId(INVITED), 'the invited shift exists').toBeGreaterThan(0);
  await expect(rowFor(page, INVITED)).toHaveCount(0);

  // Three seeded shifts, and nothing else on the account.
  await expect(page.locator('#joblist tbody tr')).toHaveCount(3);
});

test('the dashboard counts what the screen lists', async ({ page }) => {
  await openAsApplicant(page, 'applicant/applied_jobs');
  const listed = await page.locator('#joblist tbody tr').count();

  await page.goto('applicant/dashboard');
  await settle(page);

  const tile = page.locator('.dashboard-stat', { hasText: 'My Shifts' });
  await expect(tile, 'the tile is on the dashboard').toHaveCount(1);

  // The count used to ask for sj_status = 1 as well, so an applicant with
  // nothing but bookings read 0 above a list of shifts.
  await expect(tile.locator('h4')).toHaveText(String(listed));

  await expectNoServerError(page);
});
