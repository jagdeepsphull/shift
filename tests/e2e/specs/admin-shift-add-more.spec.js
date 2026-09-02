// @ts-check
/**
 * "Add More" on the admin's Add Shift form.
 *
 * A run of shifts - the same store, rate and software on Monday, Tuesday and
 * Wednesday - used to be three trips through the form. Now the first row is
 * the shift as before, "Add More" puts another date-and-hours row under it per
 * click, each added row has an (X), and saving writes one post_job row per row
 * on the page.
 *
 * Two halves matter. The rows themselves: one per click, removable one at a
 * time, and dressed with working pickers, since a row without them is a text
 * box the calendar never opens on. And the save: every row is its own shift,
 * titled after its own id, sharing everything with the first but its date and
 * hours - and whatever the form does to one shift (booking, in particular) it
 * does to all of them.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');
const { pickShiftStore } = require('../helpers/stores');

/** What the first row opens on (SHIFT_TIME_DEFAULT), and so what a new row copies. */
const DEFAULT_TIME = '09:00 - 18:00';

const ids = {};

const cleanup = () => {
  query(`DELETE ssa FROM stu_saved_applied_jobs ssa
         JOIN users u ON u.u_id = ssa.agency_id OR u.u_id = ssa.u_id
         WHERE u.u_userid LIKE 'more%@e2e.test';`);
  query(`DELETE pj FROM post_job pj
         JOIN users u ON u.u_id = pj.u_id
         WHERE u.u_userid LIKE 'more%@e2e.test';`);
  query("DELETE FROM store WHERE s_name LIKE 'E2E More%';");
  query("DELETE FROM users WHERE u_userid LIKE 'more%@e2e.test';");
};

test.beforeEach(async ({ page }) => {
  cleanup();

  ids.city = scalar('SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1;');
  ids.province = scalar(`SELECT c_province FROM city WHERE c_id = ${ids.city};`);
  ids.skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 ORDER BY ss_id LIMIT 1;');
  ids.service = scalar('SELECT st_id FROM store_service WHERE st_status = 1 ORDER BY st_id LIMIT 1;');
  ids.shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_id LIMIT 1;');

  const user = (type, subtype, role, login, company) => query(
    `INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname,
                        u_lname, u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo,
                        u_photo, u_provice, u_city, u_address1, u_pincode, u_phone, u_email,
                        u_terms, u_status, u_collartype, created, modified, u_login_attempt,
                        u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
     VALUES (${type}, ${subtype}, ${role}, 0, '${login}', 'E2E', 'More', MD5('x'), '${company}', 0,
             '', '', '', ${ids.province}, ${ids.city}, 'x', 'x', '0000000000', '${login}', 1, 1, 0,
             NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');`,
  );

  user(1, 0, 1, 'moreemp@e2e.test', 'E2E More Pharmacy');
  ids.owner = scalar("SELECT u_id FROM users WHERE u_userid = 'moreemp@e2e.test';");

  user(2, ids.shiftFor, 0, 'moreapp@e2e.test', '');
  ids.applicant = scalar("SELECT u_id FROM users WHERE u_userid = 'moreapp@e2e.test';");

  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${ids.owner}, 'E2E More Store', '1', ${ids.province}, ${ids.city}, 'x', 'x',
                 '0000000000', '${ids.skill}', '${ids.service}', '', 1, NOW(), NOW());`);
  ids.store = scalar("SELECT s_id FROM store WHERE s_name = 'E2E More Store';");

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

const addMore = (page) => page.locator('#shift_more_add');
const rows = (page) => page.locator('[data-shift-more-row]');
const rowDate = (page, n) => page.locator('input[name="p_more_dates[]"]').nth(n);
const rowTime = (page, n) => page.locator('input[name="p_more_shift_time[]"]').nth(n);

/**
 * Set a date box through its widget. Typing into one re-opens the calendar,
 * which writes its own idea of the value back over what was typed.
 *
 * @param {import('@playwright/test').Locator} input
 * @param {string} dmy dd-mm-yyyy
 */
async function setDate(input, dmy) {
  await input.evaluate((el, value) => {
    const [d, m, y] = value.split('-').map(Number);
    window.jQuery(el).datepicker('setDate', new Date(y, m - 1, d));
  }, dmy);
  await expect(input).toHaveValue(dmy);
}

/**
 * Set an hours box through its widget, for the same reason.
 *
 * @param {import('@playwright/test').Locator} input
 * @param {string} range HH:mm - HH:mm
 */
async function setTime(input, range) {
  await input.evaluate((el, value) => {
    const picker = window.jQuery(el).data('daterangepicker');
    const [start, end] = value.split(' - ');
    picker.setStartDate(start);
    picker.setEndDate(end);
    picker.updateElement();
  }, range);
  await expect(input).toHaveValue(range);
}

/**
 * Fill everything the form insists on, with the first row on 01-09-2027.
 *
 * @param {import('@playwright/test').Page} page
 */
async function fillShift(page) {
  await page.goto('sadmin/postjobs/add');
  await settle(page);

  await pickShiftStore(page, ids.store);

  // The store's own defaults tick the required group; wait for them rather
  // than ticking by hand, or the submit races the fill.
  await expect
    .poll(() => page.locator('#cbg_p_skills input:checked').count())
    .toBeGreaterThan(0);

  await page.selectOption('select[name="p_shift_for"]', String(ids.shiftFor));
  await page.fill('input[name="p_hourly_rate"]', '40');
  await page.fill('input[name="p_ac_hourly_rate"]', '45');
  await setDate(page.locator('input[name="p_dates"]'), '01-09-2027');
  await expect(page.locator('input[name="p_shift_time"]')).toHaveValue(DEFAULT_TIME);
}

const submit = async (page) => {
  await page.click('input[name="savedata"], button[name="savedata"]');
  await settle(page);
};

/** The shifts this run created, oldest first: [p_id, p_dates, p_shift_time, p_date_start, p_job_title]. */
const savedShifts = () => {
  const out = query(
    `SELECT p_id, p_dates, p_shift_time, p_date_start, p_job_title
       FROM post_job WHERE u_id = ${ids.owner} ORDER BY p_id;`,
  );

  return out === '' ? [] : out.split('\n').map((line) => line.split('\t'));
};

test('a row per click, each with its own (X), and none of it on the edit form', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');
  await settle(page);

  await expect(rows(page), 'a fresh form has only the first row').toHaveCount(0);

  await addMore(page).click();
  await addMore(page).click();
  await addMore(page).click();
  await expect(rows(page)).toHaveCount(3);

  // Each new row proposes the hours of the row above it, which here is the
  // first row's default all the way down.
  for (let n = 0; n < 3; n++) {
    await expect(rowTime(page, n)).toHaveValue(DEFAULT_TIME);
  }

  // Dressed, not bare: both pickers are on every added row. Without them the
  // date is a text box the calendar never opens on.
  const dressed = await page.locator('[data-shift-more-row]').evaluateAll((els) => els.map((el) => {
    const $ = window.jQuery;
    return Boolean($(el).find('.date').data('datepicker')) && Boolean($(el).find('.timePicker').data('daterangepicker'));
  }));
  expect(dressed).toEqual([true, true, true]);

  // Fill the outer two, take out the middle one, and the outer two survive
  // with what was put in them - (X) removes its own row and nothing else.
  await setDate(rowDate(page, 0), '02-09-2027');
  await setDate(rowDate(page, 2), '04-09-2027');
  await page.locator('[data-shift-more-remove]').nth(1).click();

  await expect(rows(page)).toHaveCount(2);
  await expect(rowDate(page, 0)).toHaveValue('02-09-2027');
  await expect(rowDate(page, 1)).toHaveValue('04-09-2027');

  // The first row is the shift itself and has no (X).
  await expect(page.locator('input[name="p_dates"]')).toHaveCount(1);
  await expect(page.locator('[data-shift-more-remove]')).toHaveCount(2);

  // Add only. The edit form works on one shift, and offers nothing to add.
  query(`INSERT INTO post_job (u_id, p_job_title, p_store_id, p_province, p_city, p_shift_for,
                               p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
                               p_skills, p_services, p_status, p_approved, created, modified)
         VALUES (${ids.owner}, 'E2E-MORE-EDIT', ${ids.store}, ${ids.province}, ${ids.city}, ${ids.shiftFor},
                 40, 45, '01-09-2027', '2027-09-01', '${DEFAULT_TIME}', '', '', 1, 0, NOW(), NOW());`);
  const shift = scalar(`SELECT p_id FROM post_job WHERE p_job_title = 'E2E-MORE-EDIT';`);

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await settle(page);
  await expectNoServerError(page);

  await expect(addMore(page)).toHaveCount(0);
  await expect(rows(page)).toHaveCount(0);
});

test('every row on the page is saved as a shift of its own', async ({ page }) => {
  await fillShift(page);

  await addMore(page).click();
  await setDate(rowDate(page, 0), '02-09-2027');

  await addMore(page).click();
  await setDate(rowDate(page, 1), '03-09-2027');
  // Its own hours, so the save is shown to take each row's, not the first's.
  await setTime(rowTime(page, 1), '07:00 - 12:00');

  await page.selectOption('#p_approved', '1');
  await submit(page);
  await expectNoServerError(page);

  const saved = savedShifts();
  expect(saved.map((s) => s[1]), 'one shift per row, in the order they were on the page')
    .toEqual(['01-09-2027', '02-09-2027', '03-09-2027']);
  expect(saved.map((s) => s[2]), 'each with its own hours')
    .toEqual([DEFAULT_TIME, DEFAULT_TIME, '07:00 - 12:00']);
  expect(saved.map((s) => s[3]), 'and the sortable date read off each')
    .toEqual(['2027-09-01', '2027-09-02', '2027-09-03']);

  for (const [id, , , , title] of saved) {
    expect(title, 'titled after its own id, like any shift').toBe(`PAS-${id}`);
  }

  // Everything but the date and hours is the first row's.
  expect(scalar(
    `SELECT COUNT(DISTINCT p_store_id, p_hourly_rate, p_ac_hourly_rate, p_skills, p_approved)
       FROM post_job WHERE u_id = ${ids.owner};`,
  )).toBe('1');

  // The list says how many went in, which with several rows is the thing to check.
  await expect(page.locator('.alert-success')).toContainText('3 shifts have been added');
});

test('a save that comes back keeps the added rows', async ({ page }) => {
  await fillShift(page);

  await addMore(page).click();
  await setDate(rowDate(page, 0), '02-09-2027');

  // A rate the server refuses. The box's own `max` would stop the submit
  // first, so it is taken off - this is the hand-edited form the server
  // guards against, and the added row has to come back with the rest.
  await page.locator('input[name="p_hourly_rate"]').evaluate((el) => el.removeAttribute('max'));
  await page.fill('input[name="p_hourly_rate"]', '5000');

  await submit(page);
  await expectNoServerError(page);

  await expect(page.locator('.alert-danger')).toContainText('Hourly Rate');
  expect(count('post_job', `u_id = ${ids.owner}`), 'nothing was saved').toBe(0);

  await expect(rows(page), 'the added row is back').toHaveCount(1);
  await expect(rowDate(page, 0)).toHaveValue('02-09-2027');
  await expect(rowTime(page, 0)).toHaveValue(DEFAULT_TIME);
  await expect(page.locator('input[name="p_dates"]'), 'as is the first').toHaveValue('01-09-2027');

  // And it is a live row, not a picture of one: (X) still takes it off.
  await page.locator('[data-shift-more-remove]').first().click();
  await expect(rows(page)).toHaveCount(0);
});

test('naming an applicant books them on every shift the rows made', async ({ page }) => {
  await fillShift(page);

  await addMore(page).click();
  await setDate(rowDate(page, 0), '02-09-2027');

  await page.selectOption('#sj_applicant_id', String(ids.applicant));
  await page.fill('#sj_admin_comment', 'Both days, agreed on the phone.');

  await submit(page);
  await expectNoServerError(page);

  const saved = savedShifts();
  expect(saved).toHaveLength(2);

  for (const [id, date] of saved) {
    expect(scalar(`SELECT p_approved FROM post_job WHERE p_id = ${id};`),
      `the shift on ${date} is closed to everybody else`).toBe('3');

    const booking = query(
      `SELECT u_id, sj_is_approved, sj_admin_comment FROM stu_saved_applied_jobs WHERE p_id = ${id};`,
    ).split('\t');
    expect(booking, `and booked for the applicant on ${date}`)
      .toEqual([String(ids.applicant), '1', 'Both days, agreed on the phone.']);
  }
});
