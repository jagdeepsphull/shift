// @ts-check
/**
 * Booking an applicant straight from the admin's "Add Shift" form.
 *
 * The section exists for a shift already agreed off the site: rather than post
 * it, wait for that person to apply and approve them on the applications
 * screen, the administrator names them here and the booking is written with the
 * shift. What matters is that it produces exactly what the applications screen
 * produces - an approved row, and a shift closed behind it - because every
 * later screen and report reads those two and nothing else.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');
const { pickShiftStore } = require('../helpers/stores');

const ids = {};

const cleanup = () => {
  // Everything hangs off the two seeded logins, so they are what the clean-up
  // follows - and in dependency order, children first.
  query(`DELETE ssa FROM stu_saved_applied_jobs ssa
         JOIN users u ON u.u_id = ssa.agency_id OR u.u_id = ssa.u_id
         WHERE u.u_userid LIKE 'book%@e2e.test';`);
  query(`DELETE pj FROM post_job pj
         JOIN users u ON u.u_id = pj.u_id
         WHERE u.u_userid LIKE 'book%@e2e.test';`);
  query("DELETE FROM store WHERE s_name LIKE 'E2E Book%';");
  query("DELETE FROM users WHERE u_userid LIKE 'book%@e2e.test';");
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
     VALUES (${type}, ${subtype}, ${role}, 0, '${login}', 'E2E', 'Book', MD5('x'), '${company}', 0,
             '', '', '', ${ids.province}, ${ids.city}, 'x', 'x', '0000000000', '${login}', 1, 1, 0,
             NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');`,
  );

  user(1, 0, 1, 'bookemp@e2e.test', 'E2E Book Pharmacy');
  ids.owner = scalar("SELECT u_id FROM users WHERE u_userid = 'bookemp@e2e.test';");

  user(2, ids.shiftFor, 0, 'bookapp@e2e.test', '');
  ids.applicant = scalar("SELECT u_id FROM users WHERE u_userid = 'bookapp@e2e.test';");

  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${ids.owner}, 'E2E Book Store', '1', ${ids.province}, ${ids.city}, 'x', 'x',
                 '0000000000', '${ids.skill}', '${ids.service}', '', 1, NOW(), NOW());`);
  ids.store = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Book Store';");

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

/**
 * Fill everything the shift form insists on, leaving the booking section alone.
 *
 * @param {import('@playwright/test').Page} page
 */
async function fillShift(page) {
  await page.goto('sadmin/postjobs/add');

  // The store is the whole of the question: it belongs to one employer, so
  // choosing it chooses them, and the shift is saved against that owner. It is
  // reached through its group, which is the first of the two dropdowns.
  await pickShiftStore(page, ids.store);

  // The store's own defaults tick the two required groups; wait for them
  // rather than ticking by hand, or the submit races the fill.
  await expect
    .poll(() => page.locator('#cbg_p_skills input:checked').count())
    .toBeGreaterThan(0);

  await page.selectOption('select[name="p_shift_for"]', String(ids.shiftFor));
  await page.fill('input[name="p_hourly_rate"]', '40');
  await page.fill('input[name="p_ac_hourly_rate"]', '45');
  // Through the widget, not the input: typing into it re-opens the calendar,
  // which then writes its own idea of the value back over what was typed.
  await page.evaluate(() => {
    window.jQuery('input[name="p_dates"]').datepicker('setDate', new Date(2027, 8, 1));
  });
  await expect(page.locator('input[name="p_dates"]')).toHaveValue('01-09-2027');

  // The time picker fills itself in with the coming hour, which is a valid
  // shift time and all this form needs.
  await expect(page.locator('input[name="p_shift_time"]')).not.toHaveValue('');
}

/** The shift this run created, or '' - the seeded employer has no others. */
const seededShift = () => scalar(`SELECT p_id FROM post_job WHERE u_id = ${ids.owner};`);

const submit = async (page) => {
  await page.click('input[name="savedata"], button[name="savedata"]');
  await settle(page);
};

const bookingOf = (shift) => query(
  `SELECT u_id, agency_id, sj_status, sj_is_approved, sj_admin_comment
     FROM stu_saved_applied_jobs WHERE p_id = ${shift};`,
).split('\t');

test('naming an applicant books them and closes the shift', async ({ page }) => {
  await fillShift(page);

  await expect(page.locator('#sj_applicant_id')).toBeVisible();
  await page.selectOption('#sj_applicant_id', String(ids.applicant));
  await page.fill('#sj_admin_comment', 'Agreed on the phone.');

  await submit(page);
  await expectNoServerError(page);

  const shift = seededShift();
  expect(shift, 'the shift was saved').not.toBe('');

  expect(scalar(`SELECT p_approved FROM post_job WHERE p_id = ${shift};`),
    'a booked shift is closed to everybody else').toBe('3');

  const [applicant, agency, status, approved, comment] = bookingOf(shift);
  expect(applicant).toBe(String(ids.applicant));
  expect(agency, 'the booking belongs to the shift owner').toBe(String(ids.owner));
  expect(status, 'starts where an approved application ends up').toBe('6');
  expect(approved).toBe('1');
  expect(comment).toBe('Agreed on the phone.');
});

test('leaving it alone posts an ordinary, unbooked shift', async ({ page }) => {
  await fillShift(page);
  await page.selectOption('#p_approved', '1');

  await submit(page);
  await expectNoServerError(page);

  const shift = seededShift();
  expect(shift, 'the shift was saved').not.toBe('');

  expect(scalar(`SELECT p_approved FROM post_job WHERE p_id = ${shift};`),
    'the status dropdown still decides').toBe('1');
  expect(count('stu_saved_applied_jobs', `p_id = ${shift}`)).toBe(0);
});

test('an applicant deactivated since the page loaded saves nothing', async ({ page }) => {
  await fillShift(page);
  await page.selectOption('#sj_applicant_id', String(ids.applicant));

  // Between loading the form and pressing save, the account goes inactive.
  query(`UPDATE users SET u_status = 0 WHERE u_id = ${ids.applicant};`);

  await submit(page);
  await expectNoServerError(page);

  await expect(page.locator('.alert-danger')).toContainText('no longer active');
  expect(count('post_job', `u_id = ${ids.owner}`),
    'a shift meant to be booked must not be left unbooked').toBe(0);
});

test('the booked applicant shows on the applications screen', async ({ page }) => {
  await fillShift(page);
  await page.selectOption('#sj_applicant_id', String(ids.applicant));
  await submit(page);

  const title = scalar(`SELECT p_job_title FROM post_job WHERE p_id = ${seededShift()};`);

  await page.goto('sadmin/applications?filter=booked');
  await expectNoServerError(page);

  // The list is paged, and a shift booked today is nowhere near the first page.
  await filterTable(page, title);
  await expect(page.locator('#example1')).toContainText(title);
});
