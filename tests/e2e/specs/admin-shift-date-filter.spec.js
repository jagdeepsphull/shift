// @ts-check
/**
 * The date-range filter in the Manage Shifts and Job Applications toolbars.
 *
 * Three shifts are seeded around today - one on it, one inside the next week,
 * one years out - so each preset has rows it must keep and rows it must drop.
 * Each is applied for as well, so the same three dates are on the applications
 * screen, which reads its date off the shift behind the application.
 * The dates are worked out here in Node rather than by MySQL: the presets are
 * built in the browser with moment, and the two clocks on this machine do not
 * agree (see the shared note about NOW() versus PHP's date()). Node and the
 * browser share one clock, so a shift seeded as "today" here is the same day
 * the picker means by Today.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

/** Today plus `days`, as { iso: 'YYYY-MM-DD', typed: 'dd-mm-yyyy' }. */
function offsetDay(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);

  const pad = (n) => String(n).padStart(2, '0');
  const iso = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

  return { iso, typed: `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${d.getFullYear()}` };
}

const TODAY = offsetDay(0);
const IN_THREE_DAYS = offsetDay(3);

const SHIFTS = [
  { title: 'E2E-DATEFILTER-TODAY', ...TODAY },
  { title: 'E2E-DATEFILTER-SOON', ...IN_THREE_DAYS },
  { title: 'E2E-DATEFILTER-FAR', iso: '2031-06-17', typed: '17-06-2031' },
];

function removeFixture() {
  // The applications first: they are the rows pointing at the shifts, and
  // MySQL will not let a subquery in a DELETE read the table being deleted
  // from without the extra derived table around it.
  query(`
    DELETE FROM stu_saved_applied_jobs
     WHERE p_id IN (SELECT p_id FROM (SELECT p_id FROM post_job WHERE p_job_title LIKE 'E2E-DATEFILTER-%') x);
  `);
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-DATEFILTER-%';");
}

test.beforeAll(() => {
  removeFixture();

  const uid = scalar('SELECT u_id FROM users WHERE u_usertype = 1 AND u_status = 1 LIMIT 1;');

  // Whoever applies for the three shifts. The applications screen inner-joins
  // the agency but only left-joins the applicant, so a site with no pharmacist
  // on it still gets three rows to filter - they just show a blank name.
  const applicant = scalar('SELECT u_id FROM users WHERE u_usertype = 2 AND u_status = 1 LIMIT 1;') || uid;
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
        (${uid}, 'E2E Pharmacy', '${shift.title}', 0, ${province}, ${city}, ${shiftFor},
         30, 30, '${shift.typed}', '${shift.iso}', '09:00 - 17:00',
         '', '', 'Seeded by the end-to-end suite.', 0, 1, 0,
         NOW(), NOW());
    `);

    // One application on each, so the same three dates are on the applications
    // list. Left pending: what the filter reads is the shift's date, not the
    // state of the booking.
    query(`
      INSERT INTO stu_saved_applied_jobs
        (u_id, agency_id, p_id, sj_applied_date, sj_status, sj_is_approved,
         sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments,
         created, modified)
      VALUES
        (${applicant}, ${uid},
         (SELECT p_id FROM post_job WHERE p_job_title = '${shift.title}' LIMIT 1),
         NOW(), 1, 0, '', '', '', 0, NOW(), NOW());
    `);
  }
});

test.afterAll(() => {
  removeFixture();
});

/** The seeded shift ids visible in the table right now. */
async function visibleFixtureRows(page) {
  const titles = await page.locator('#example1 tbody tr td:nth-child(1)').allTextContents();

  return titles.map((t) => t.trim()).filter((t) => t.startsWith('E2E-DATEFILTER-'));
}

/** Click one of the picker's named ranges. */
async function pickRange(page, label) {
  await page.locator('.shift-date-filter input').click();

  const range = page.locator('.daterangepicker.shift-date-picker .ranges li', { hasText: label });
  await expect(range.first()).toBeVisible();
  await range.first().click();
  await settle(page);
}

test('the shift list offers a date range filter beside its export buttons', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/postjobs');
  await settle(page);

  const box = page.locator('.shift-date-filter input');
  await expect(box, 'the filter sits in the table toolbar').toBeVisible();

  // It starts empty: the list is showing everything until a range is chosen.
  await expect(box).toHaveValue('');
  await expect(box).toHaveAttribute('placeholder', 'All shift dates');
  await expect(page.locator('.shift-date-filter__clear')).toBeHidden();

  // The presets from the design, in order, plus Custom Range.
  await box.click();

  const picker = page.locator('.daterangepicker.shift-date-picker');
  await expect(picker).toBeVisible();
  await expect(picker.locator('.ranges li')).toHaveText([
    'Today',
    'Tomorrow',
    'Next 7 Days',
    'Next 30 Days',
    'This Month',
    'Next Month',
    'Custom Range',
  ]);

  // The admin header hides the calendar on every other daterangepicker on the
  // page - the hours boxes on the shift form. This one has to keep it.
  await expect(picker.locator('.drp-calendar.left .calendar-table')).toBeVisible();
  await expect(picker.locator('.ranges')).toBeVisible();

  await expectNoServerError(page);
});

test('each preset keeps the shifts inside it and drops the rest', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/postjobs');
  await settle(page);

  // Unfiltered, all three seeded shifts are somewhere in the list - which is
  // paged, so narrow to them with the search box first.
  await page.fill('#example1_filter input', 'E2E-DATEFILTER');
  await settle(page);
  expect((await visibleFixtureRows(page)).sort()).toEqual([
    'E2E-DATEFILTER-FAR',
    'E2E-DATEFILTER-SOON',
    'E2E-DATEFILTER-TODAY',
  ]);

  // Today: only the shift dated today.
  await pickRange(page, 'Today');
  await expect(page.locator('.shift-date-filter input')).toHaveValue('Today');
  expect(await visibleFixtureRows(page)).toEqual(['E2E-DATEFILTER-TODAY']);

  // Next 7 Days: today's and the one three days out, never the 2031 one.
  await pickRange(page, 'Next 7 Days');
  await expect(page.locator('.shift-date-filter input')).toHaveValue('Next 7 Days');
  expect((await visibleFixtureRows(page)).sort()).toEqual([
    'E2E-DATEFILTER-SOON',
    'E2E-DATEFILTER-TODAY',
  ]);

  // Tomorrow: none of the three, and the table says so rather than going blank.
  await pickRange(page, 'Tomorrow');
  expect(await visibleFixtureRows(page)).toEqual([]);

  await expectNoServerError(page);
});

test('Clear puts the whole list back and leaves the search box alone', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/postjobs');
  await settle(page);

  await page.fill('#example1_filter input', 'E2E-DATEFILTER');
  await settle(page);

  await pickRange(page, 'Today');
  expect(await visibleFixtureRows(page)).toEqual(['E2E-DATEFILTER-TODAY']);

  const clear = page.locator('.shift-date-filter__clear');
  await expect(clear).toBeVisible();
  await clear.click();
  await settle(page);

  await expect(page.locator('.shift-date-filter input')).toHaveValue('');
  await expect(clear).toBeHidden();

  // The typed search survives clearing the dates: the two filters are separate.
  await expect(page.locator('#example1_filter input')).toHaveValue('E2E-DATEFILTER');
  expect((await visibleFixtureRows(page)).sort()).toEqual([
    'E2E-DATEFILTER-FAR',
    'E2E-DATEFILTER-SOON',
    'E2E-DATEFILTER-TODAY',
  ]);

  await expectNoServerError(page);
});

test('the applications list filters on its shift date as well', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/applications');
  await settle(page);

  const box = page.locator('.shift-date-filter input');
  await expect(box, 'the filter sits in the applications toolbar').toBeVisible();
  await expect(box).toHaveValue('');
  await expect(box).toHaveAttribute('placeholder', 'All shift dates');

  // The applications behind the three seeded shifts, narrowed to with the
  // search box - the column showing the shift is what the fixture names are in.
  await page.fill('#example1_filter input', 'E2E-DATEFILTER');
  await settle(page);
  expect((await visibleFixtureRows(page)).sort()).toEqual([
    'E2E-DATEFILTER-FAR',
    'E2E-DATEFILTER-SOON',
    'E2E-DATEFILTER-TODAY',
  ]);

  // Next 7 Days: the application on today's shift and the one three days out.
  // The 2031 shift has an application too, and it is the date on the shift -
  // not the date the application was made, which is today for all three - that
  // decides which rows stay.
  await pickRange(page, 'Next 7 Days');
  expect((await visibleFixtureRows(page)).sort()).toEqual([
    'E2E-DATEFILTER-SOON',
    'E2E-DATEFILTER-TODAY',
  ]);

  await pickRange(page, 'Today');
  expect(await visibleFixtureRows(page)).toEqual(['E2E-DATEFILTER-TODAY']);

  await page.locator('.shift-date-filter__clear').click();
  await settle(page);
  expect((await visibleFixtureRows(page)).sort()).toEqual([
    'E2E-DATEFILTER-FAR',
    'E2E-DATEFILTER-SOON',
    'E2E-DATEFILTER-TODAY',
  ]);

  await expectNoServerError(page);
});

test('the other admin listings get no date filter', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/applicant');
  await settle(page);

  await expect(page.locator('#example1')).toBeVisible();
  await expect(page.locator('.shift-date-filter')).toHaveCount(0);

  await expectNoServerError(page);
});
