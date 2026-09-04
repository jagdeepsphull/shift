// @ts-check
/**
 * The date filter on the applicant's My Shifts screen.
 *
 * The same control the owner has on All Shifts, on the same `#joblist` table
 * and out of the same partial - so what is worth testing here is not the
 * picker's own behaviour again but that it is wired to this screen's column.
 * The two lists number their columns differently: the owner's Shift Date is
 * column 4 and the applicant's is column 3, and a filter pointed one column out
 * would read a shift time or a store name as a date and empty the table.
 *
 * Four shifts are seeded and booked to the one applicant - one already gone,
 * one today, one inside the next week and one years out - so every assertion
 * below has both rows it must keep and rows it must drop. The dates are worked
 * out here in Node rather than by MySQL: the presets are built in the browser
 * with moment, and the two clocks on this machine do not agree (see the shared
 * note about NOW() versus PHP's date()). Node and the browser share one clock,
 * so a shift seeded as "today" here is the same day the picker means by Today.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const {
  AGENCY,
  APPLICANT,
  seedShiftFixture,
  removeShiftFixture,
  loginAsApplicant,
} = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

/** Today plus `days`, as { iso: 'YYYY-MM-DD', typed: 'dd-mm-yyyy' }. */
function offsetDay(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);

  const pad = (n) => String(n).padStart(2, '0');

  return {
    iso: `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`,
    typed: `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${d.getFullYear()}`,
  };
}

const PREFIX = 'E2E-ADFILT-';

/** Title -> the day it falls on, relative to today. */
const SHIFTS = [
  { title: `${PREFIX}GONE`, day: offsetDay(-30) },
  { title: `${PREFIX}TODAY`, day: offsetDay(0) },
  { title: `${PREFIX}WEEK`, day: offsetDay(3) },
  { title: `${PREFIX}FAR`, day: offsetDay(400) },
];

/** Just the dated shifts; the accounts belong to the shared fixture. */
function removeShifts() {
  query(`
    DELETE FROM stu_saved_applied_jobs
     WHERE p_id IN (SELECT p_id FROM (SELECT p_id FROM post_job WHERE p_job_title LIKE '${PREFIX}%') x);
  `);
  query(`DELETE FROM post_job WHERE p_job_title LIKE '${PREFIX}%';`);
}

test.beforeAll(() => {
  removeShifts();

  // The shared fixture brings both logins; the dated shifts below hang off its
  // agency and are booked to its applicant, so My Shifts lists those as well as
  // the fixture's own three.
  seedShiftFixture();

  const agencyId = Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${AGENCY.user}';`));
  const applicantId = Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${APPLICANT.user}';`));
  const province = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;');
  const city = scalar('SELECT c_id FROM city WHERE c_status = 1 LIMIT 1;');
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  for (const shift of SHIFTS) {
    query(`
      INSERT INTO post_job
        (u_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
         p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
         p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
      VALUES
        (${agencyId}, 'E2E Pharmacy', '${shift.title}', 0, ${province}, ${city}, ${shiftFor},
         30, 30, '${shift.day.typed}', '${shift.day.iso}', '09:00 - 17:00',
         '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());
    `);

    const pid = scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${shift.title}';`);

    // Booked, so each row reads the way this screen does for a shift the
    // applicant is actually on.
    query(`
      INSERT INTO stu_saved_applied_jobs
        (u_id, agency_id, p_id, sj_applied_date, sj_status, sj_is_approved,
         sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments,
         created, modified)
      VALUES
        (${applicantId}, ${agencyId}, ${pid}, NOW(), 1, 1,
         '', '', '', 0, NOW(), NOW());
    `);
  }
});

test.afterAll(() => {
  removeShifts();
  removeShiftFixture();
});

/**
 * The seeded shift titles currently on the page.
 *
 * Column 0 is the internal application id, hidden by a class rather than by
 * DataTables, so its cell is still in the row and the title is the second one.
 */
async function shown(page) {
  const cells = await page.locator('#joblist tbody tr td:nth-child(2)').allInnerTexts();

  return cells.map((t) => t.trim()).filter((t) => t.startsWith(PREFIX));
}

test('Upcoming Shifts drops what has been and gone, and gives it back', async ({ page }) => {
  await loginAsApplicant(page);
  await page.goto('applicant/applied_jobs');
  await settle(page);

  const box = page.locator('.ps-date-filter__input');
  const upcoming = page.locator('#joblist-upcoming');
  const clear = page.locator('.ps-date-filter__clear');

  // My Shifts is every shift the applicant has ever been on: it opens showing
  // all of them, filter off.
  await expect(box).toHaveAttribute('placeholder', 'Any shift date');
  await expect(box).toHaveValue('');
  await expect(clear).toBeHidden();
  expect(await shown(page)).toContain(`${PREFIX}GONE`);

  await upcoming.click();

  // Lit, and saying in the box what it has done - the filtered list is not the
  // only place the state shows.
  await expect(upcoming).toHaveClass(/is-on/);
  await expect(upcoming).toHaveAttribute('aria-pressed', 'true');
  await expect(box).toHaveValue('Today onwards');
  await expect(clear).toBeVisible();

  const upcomingRows = await shown(page);

  // Today counts as upcoming - a shift is still worked on the morning of its
  // own day - and there is no last day, so the one years out stays too.
  expect(upcomingRows).toEqual(
    expect.arrayContaining([`${PREFIX}TODAY`, `${PREFIX}WEEK`, `${PREFIX}FAR`]),
  );
  expect(upcomingRows).not.toContain(`${PREFIX}GONE`);

  // A second click is the way back, without reloading the page.
  await upcoming.click();
  await expect(upcoming).not.toHaveClass(/is-on/);
  await expect(box).toHaveValue('');
  expect(await shown(page)).toContain(`${PREFIX}GONE`);

  await expectNoServerError(page);
});

test('a range off the calendar reads this screen own shift-date column', async ({ page }) => {
  await loginAsApplicant(page);
  await page.goto('applicant/applied_jobs');
  await settle(page);

  const box = page.locator('.ps-date-filter__input');
  const upcoming = page.locator('#joblist-upcoming');
  const clear = page.locator('.ps-date-filter__clear');

  await upcoming.click();
  await expect(upcoming).toHaveClass(/is-on/);

  await box.click();

  // This area's header loads the picker's own stylesheet and hides neither half
  // of it, so both the range list and the calendar are there to click.
  const picker = page.locator('.daterangepicker.ps-shift-picker');
  await expect(picker.locator('.ranges')).toBeVisible();
  await expect(picker.locator('.calendar-table').first()).toBeVisible();

  await picker.locator('.ranges li', { hasText: 'Next 7 Days' }).click();

  // One window, however it was set: the button cannot stay lit over a range
  // that is not the one it stands for.
  await expect(upcoming).not.toHaveClass(/is-on/);
  await expect(upcoming).toHaveAttribute('aria-pressed', 'false');
  await expect(box).toHaveValue('Next 7 Days');

  // The window is read off column 3. Pointed at any other column this would
  // empty the table rather than keep these two.
  const weekRows = await shown(page);
  expect(weekRows).toEqual(
    expect.arrayContaining([`${PREFIX}TODAY`, `${PREFIX}WEEK`]),
  );
  expect(weekRows).not.toContain(`${PREFIX}GONE`);
  expect(weekRows, 'a shift beyond the window drops out').not.toContain(`${PREFIX}FAR`);

  await clear.click();
  await expect(clear).toBeHidden();
  await expect(box).toHaveValue('');
  expect(await shown(page)).toContain(`${PREFIX}GONE`);

  await expectNoServerError(page);
});
