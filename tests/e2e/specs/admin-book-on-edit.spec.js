// @ts-check
/**
 * Changing the booking on a shift that already has one, from the admin's
 * "Edit Shift" form.
 *
 * A booked shift used to be frozen outright, which left no way to deal with the
 * ordinary case of an applicant ringing up to say they cannot make it: the
 * booking could only be rejected on the applications screen, and the shift
 * stayed closed with nobody on it. It is now editable until the day it is
 * worked. What matters here is the pair of writes that every later screen and
 * report reads - who holds the approved row, and whether the shift is closed
 * behind them - plus the cut-off, which is the shift date itself.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const ids = {};

const cleanup = () => {
  query(`DELETE ssa FROM stu_saved_applied_jobs ssa
         JOIN users u ON u.u_id = ssa.agency_id OR u.u_id = ssa.u_id
         WHERE u.u_userid LIKE 'bookedit%@e2e.test';`);
  query(`DELETE pj FROM post_job pj
         JOIN users u ON u.u_id = pj.u_id
         WHERE u.u_userid LIKE 'bookedit%@e2e.test';`);
  query("DELETE FROM store WHERE s_name LIKE 'E2E BookEdit%';");
  query("DELETE FROM users WHERE u_userid LIKE 'bookedit%@e2e.test';");
};

/**
 * A shift on the given date, seeded straight into the database - the add form
 * is the other spec's subject, and this one needs shifts either side of today.
 *
 * @param {string} isoDate  yyyy-mm-dd, as `p_date_start` holds it
 * @param {number} approved `p_approved`
 * @returns {string} the new p_id
 */
const seedShift = (isoDate, approved) => {
  const [y, m, d] = isoDate.split('-');

  // Both statements in the one call: `query()` opens a connection per call, and
  // LAST_INSERT_ID() is per connection - asking for it separately returns 0.
  const shift = scalar(
    `INSERT INTO post_job (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province,
                           p_city, p_shift_for, p_hourly_rate, p_ac_hourly_rate, p_dates,
                           p_date_start, p_shift_time, p_skills, p_services,
                           p_additional_details, p_jobinfo, p_featured, p_status, p_approved,
                           created, modified)
     VALUES (${ids.owner}, ${ids.store}, 'E2E BookEdit Pharmacy', 'E2E-PENDING', 0,
             ${ids.province}, ${ids.city}, ${ids.shiftFor}, 40, 45, '${d}-${m}-${y}',
             '${isoDate}', '09:00 AM - 05:00 PM', '${ids.skill}', '${ids.service}', '', '',
             0, 1, ${approved}, NOW(), NOW());
     SELECT LAST_INSERT_ID();`,
  );

  // The same title the controller gives a shift on add, so the row reads the
  // way every other shift in the database does.
  query(`UPDATE post_job SET p_job_title = 'PAS-${shift}' WHERE p_id = ${shift};`);

  return shift;
};

/** Book somebody on a shift, exactly as an approved application leaves it. */
const seedBooking = (shift, applicant) => query(
  `INSERT INTO stu_saved_applied_jobs (u_id, agency_id, p_id, sj_status, sj_is_approved,
                                       sj_applied_date, sj_accept_date, sj_admin_comment,
                                       sj_applied_desc, sj_resubmit_comments,
                                       sj_rejected_comments, created, modified)
   VALUES (${applicant}, ${ids.owner}, ${shift}, 6, 1, NOW(), NOW(), 'Agreed on the phone.',
           '', '', 0, NOW(), NOW());`,
);

const approvalOf = (shift, applicant) => scalar(
  `SELECT sj_is_approved FROM stu_saved_applied_jobs
    WHERE p_id = ${shift} AND u_id = ${applicant};`,
);

const statusOf = (shift) => scalar(`SELECT p_approved FROM post_job WHERE p_id = ${shift};`);

const submit = async (page) => {
  await page.click('input[name="savedata"], button[name="savedata"]');
  await settle(page);
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
     VALUES (${type}, ${subtype}, ${role}, 0, '${login}', 'E2E', 'BookEdit', MD5('x'), '${company}',
             0, '', '', '', ${ids.province}, ${ids.city}, 'x', 'x', '0000000000', '${login}', 1, 1,
             0, NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');`,
  );

  user(1, 0, 1, 'bookeditemp@e2e.test', 'E2E BookEdit Pharmacy');
  ids.owner = scalar("SELECT u_id FROM users WHERE u_userid = 'bookeditemp@e2e.test';");

  user(2, ids.shiftFor, 0, 'bookeditapp1@e2e.test', '');
  ids.first = scalar("SELECT u_id FROM users WHERE u_userid = 'bookeditapp1@e2e.test';");

  user(2, ids.shiftFor, 0, 'bookeditapp2@e2e.test', '');
  ids.second = scalar("SELECT u_id FROM users WHERE u_userid = 'bookeditapp2@e2e.test';");

  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${ids.owner}, 'E2E BookEdit Store', '1', ${ids.province}, ${ids.city}, 'x', 'x',
                 '0000000000', '${ids.skill}', '${ids.service}', '', 1, NOW(), NOW());`);
  ids.store = scalar("SELECT s_id FROM store WHERE s_name = 'E2E BookEdit Store';");

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

test('the list offers Edit on a booked shift, but never Delete', async ({ page }) => {
  const soon = seedShift('2027-09-01', 3);
  const gone = seedShift('2020-09-01', 3);
  seedBooking(soon, ids.first);
  seedBooking(gone, ids.first);

  await page.goto('sadmin/postjobs');
  await expectNoServerError(page);

  // The list is long and paged, so each shift is looked at on its own.
  await filterTable(page, `PAS-${soon}`);
  const upcoming = page.locator('#example1 tbody tr', { hasText: `PAS-${soon}` });
  await expect(upcoming.getByRole('link', { name: 'Edit' })).toHaveCount(1);
  await expect(upcoming.getByRole('link', { name: 'Delete' }),
    'a booking is a record, and stays').toHaveCount(0);

  await filterTable(page, `PAS-${gone}`);
  const worked = page.locator('#example1 tbody tr', { hasText: `PAS-${gone}` });
  await expect(worked.getByRole('link', { name: 'Edit' })).toHaveCount(0);
});

test('the form opens on whoever is booked', async ({ page }) => {
  const shift = seedShift('2027-09-01', 3);
  seedBooking(shift, ids.first);

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await expectNoServerError(page);

  await expect(page.locator('#sj_applicant_id')).toHaveValue(String(ids.first));
});

test('choosing somebody else moves the booking to them', async ({ page }) => {
  const shift = seedShift('2027-09-01', 3);
  seedBooking(shift, ids.first);

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await page.selectOption('#sj_applicant_id', String(ids.second));
  await page.fill('#sj_admin_comment', 'Covering for the original booking.');

  await submit(page);
  await expectNoServerError(page);

  expect(approvalOf(shift, ids.first), 'the applicant who dropped out is off it').toBe('2');
  expect(approvalOf(shift, ids.second), 'the new applicant holds the booking').toBe('1');
  expect(statusOf(shift), 'somebody is on it, so it stays closed').toBe('3');
  expect(count('stu_saved_applied_jobs', `p_id = ${shift} AND sj_is_approved = 1`),
    'exactly one applicant is booked').toBe(1);
});

test('clearing the booking puts the shift back on the board', async ({ page }) => {
  const shift = seedShift('2027-09-01', 3);
  seedBooking(shift, ids.first);

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await page.selectOption('#sj_applicant_id', '');

  await submit(page);
  await expectNoServerError(page);

  expect(approvalOf(shift, ids.first)).toBe('2');
  expect(statusOf(shift), 'nobody on it, so it takes applications again').toBe('1');
});

test('leaving the picker alone leaves the booking alone', async ({ page }) => {
  const shift = seedShift('2027-09-01', 3);
  seedBooking(shift, ids.first);

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await submit(page);
  await expectNoServerError(page);

  expect(approvalOf(shift, ids.first), 'still booked').toBe('1');
  expect(statusOf(shift)).toBe('3');
});

test('a shift whose date has gone by cannot be edited at all', async ({ page }) => {
  const shift = seedShift('2020-09-01', 3);
  seedBooking(shift, ids.first);

  // The URL first: the page is still redirecting off the frozen shift, and
  // reading its content mid-navigation is a race.
  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await expect(page).toHaveURL(/\/sadmin\/postjobs$/);
  await expectNoServerError(page);

  await expect(page.locator('.alert-danger')).toContainText('no longer be modified');
  expect(approvalOf(shift, ids.first), 'and nothing about it changed').toBe('1');
});

test('today is too late: the shift is being worked', async ({ page }) => {
  const shift = seedShift(new Date().toISOString().slice(0, 10), 3);
  seedBooking(shift, ids.first);

  await page.goto(`sadmin/postjobs/edit/${shift}`);

  await expect(page).toHaveURL(/\/sadmin\/postjobs$/);
  await expect(page.locator('.alert-danger')).toContainText('no longer be modified');
});

test('an open shift with no booking can be booked from the edit form', async ({ page }) => {
  const shift = seedShift('2027-09-01', 1);

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await page.selectOption('#sj_applicant_id', String(ids.first));

  await submit(page);
  await expectNoServerError(page);

  expect(approvalOf(shift, ids.first)).toBe('1');
  expect(statusOf(shift), 'booking closes the shift, as it does on add').toBe('3');
});
