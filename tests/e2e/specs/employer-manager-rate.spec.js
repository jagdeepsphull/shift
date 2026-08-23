// @ts-check
/**
 * What a shift pays is the group's business, not the branch's.
 *
 * A manager runs one of an owner's locations. They raise the shifts that branch
 * needs covered, but the rate on them is the group's decision, so it is not on
 * their shift form and not in the panel that reports a shift back to them.
 *
 * The half of that worth a test is not the missing field - it is the save.
 * `shiftRowFromPost()` reads a fixed list of columns straight off the post, so
 * dropping the input alone would have had every manager edit write an empty
 * rate over whatever the owner had set. The field being absent and the column
 * being left alone are two different changes, and only the second is silent
 * when it breaks.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { loginAsFrontUser } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';
const PREFIX = 'e2e.rate.';

const OWNER = { user: `${PREFIX}owner@example.com`, pass: PASSWORD };
const MANAGER = { user: `${PREFIX}manager@example.com`, pass: PASSWORD };

/** The rate the group set, which a manager's edit must not touch. */
const RATE = 47;

/** The administrator's note on a booking, which a manager is not shown either. */
const MESSAGE = 'Agreed at $52 for this one.';

/** @type {{owner: number, manager: number}} */
let user;

/** @type {number} */
let branch;

function removeFixtures() {
  query(`
    DELETE FROM stu_saved_applied_jobs WHERE p_id IN (
      SELECT p_id FROM (SELECT p_id FROM post_job WHERE p_job_title LIKE 'E2E-RATE-%') x);
  `);
  query(`
    DELETE FROM post_job WHERE u_id IN (
      SELECT u_id FROM (SELECT u_id FROM users WHERE u_userid LIKE '${PREFIX}%') x);
  `);
  query(`
    DELETE FROM store WHERE u_id IN (
      SELECT u_id FROM (SELECT u_id FROM users WHERE u_userid LIKE '${PREFIX}%') x);
  `);
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

/**
 * @param {{user: string, pass: string}} account
 * @param {{role: number, parent?: number, storeId?: number, company: string}} shape
 * @returns {number} users.u_id
 */
function seedEmployer(account, shape) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid, u_fname, u_lname,
       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, ${shape.role}, ${shape.parent || 0}, ${shape.storeId || 0}, '${account.user}', 'Rate', 'E2E',
       MD5('${account.pass}'), '${shape.company}', 0, 'E2E-RATE', '', '',
       -- A city that really is in the chosen province: both selects on the
       -- profile form are required and the city list is fetched per province.
       (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       '1 Rate Road', 'M5A 1A1', '4160000810', '${account.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${account.user}';`));
}

/** @returns {number} store.s_id */
function seedStore(ownerId, name) {
  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${ownerId}, '${name}', '${name.replace(/[^0-9A-Za-z]/g, '')}',
            (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
            (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
            '1 Rate Road', 'M5A 1A1', '4160000811', 1);
  `);

  return Number(scalar(`SELECT MAX(s_id) FROM store WHERE s_name = '${name}';`));
}

/**
 * A pending, unapproved shift at the branch, carrying the group's rate.
 *
 * @returns {number} post_job.p_id
 */
function seedShift(ownerId, title) {
  query(`
    INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
       p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES
      (${ownerId}, ${branch}, 'E2E Rate Co', '${title}', 0,
       (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1),
       ${RATE}, ${RATE}, '05-11-2027', '2027-11-05', '09:00 - 17:00',
       (SELECT ss_id FROM software_skills WHERE ss_status = 1 LIMIT 1),
       (SELECT st_id FROM store_service WHERE st_status = 1 LIMIT 1),
       'Seeded by the end-to-end suite.', 0, 0, 0, '2020-01-01 10:00:00', NOW());
  `);

  return Number(scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${title}';`));
}

test.beforeAll(() => {
  removeFixtures();

  user = { owner: 0, manager: 0 };
  user.owner = seedEmployer(OWNER, { role: 1, company: 'E2E Rate Group' });
  branch = seedStore(user.owner, 'E2E Rate Branch');
  user.manager = seedEmployer(MANAGER, {
    role: 2,
    parent: user.owner,
    storeId: branch,
    company: 'E2E Rate Branch',
  });
});

test.afterAll(removeFixtures);

/** A fresh shift per test, so one test's save cannot mislead the next. */
function seedShifts() {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-RATE-%';");

  return seedShift(user.owner, 'E2E-RATE-SHIFT');
}

/** The rate field, wherever on the form it is. */
const rateField = (page) => page.locator('input[name="p_hourly_rate"]');

test('the shift form offers a rate to the owner and none to their manager', async ({ page }) => {
  await loginAsFrontUser(page, OWNER);
  await page.goto('employer/post_job');
  await settle(page);
  await expect(rateField(page), 'the owner sets what their shifts pay').toHaveCount(1);
  await expectNoServerError(page);

  await page.goto('employer/logout');
  await loginAsFrontUser(page, MANAGER);
  await page.goto('employer/post_job');
  await settle(page);

  // The form itself is there - it is only the rate that is not.
  await expect(page.locator('select[name="p_store_id"]'), 'the manager still posts shifts').toHaveCount(1);
  await expect(rateField(page), 'but does not price them').toHaveCount(0);
  await expect(page.locator('body')).not.toContainText('Hourly Rate');
  await expectNoServerError(page);
});

test('the edit form drops the rate for a manager and keeps it for the owner', async ({ page }) => {
  const pid = seedShifts();

  await loginAsFrontUser(page, OWNER);
  await page.goto(`employer/edit_job/${pid}`);
  await settle(page);
  await expect(rateField(page)).toHaveValue(String(RATE));

  await page.goto('employer/logout');
  await loginAsFrontUser(page, MANAGER);
  await page.goto(`employer/edit_job/${pid}`);
  await settle(page);

  // Reachable - a manager edits the shifts at the branch they run - just
  // without the rate on it.
  await expect(page.locator('select[name="p_store_id"]'), 'the shift opens for them').toHaveCount(1);
  await expect(rateField(page)).toHaveCount(0);
  await expectNoServerError(page);
});

test("a manager's save leaves the rate the group set alone", async ({ page }) => {
  const pid = seedShifts();

  await loginAsFrontUser(page, MANAGER);
  await page.goto(`employer/edit_job/${pid}`);
  await settle(page);

  // Change something the manager does own, then save the form as it stands.
  // The role rather than the date or the time: both of those wear a picker
  // that rewrites the input from its own state, so a typed value does not
  // survive to the submit and the save would prove nothing.
  const seededRole = scalar(`SELECT p_shift_for FROM post_job WHERE p_id = ${pid};`);
  const otherRole = scalar(
    `SELECT sf_id FROM shift_for WHERE sf_status = 1 AND sf_id <> ${seededRole} ORDER BY sf_id LIMIT 1;`,
  );

  expect(otherRole, 'the master list offers a second role to switch to').not.toBe('');

  await page.selectOption('select[name="p_shift_for"]', otherRole);
  await page.locator('[name="savepostjob"]').click();
  await settle(page);

  // The edit landed...
  expect(
    scalar(`SELECT p_shift_for FROM post_job WHERE p_id = ${pid};`),
    "the manager's own change was saved",
  ).toBe(otherRole);

  // ...and did not take the rate with it. This is the regression: with the
  // column still read off the post, it would be 0 here.
  expect(
    scalar(`SELECT p_hourly_rate FROM post_job WHERE p_id = ${pid};`),
    'the rate is untouched',
  ).toBe(String(RATE));
});

test('a shift a manager posts carries no rate until the group sets one', async ({ page }) => {
  query("DELETE FROM post_job WHERE p_company_name = 'E2E Rate Co' OR p_store_id = " + branch + ';');

  await loginAsFrontUser(page, MANAGER);
  await page.goto('employer/post_job');
  await settle(page);

  await page.selectOption('#p_store_id', String(branch));
  await page.selectOption(
    'select[name="p_shift_for"]',
    String(scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;')),
  );
  // Off the calendar rather than typed: the picker rewrites the input when it
  // closes, so a filled value is gone by the time the form is submitted. The
  // time box needs no help - daterangepicker puts a valid range in it on load.
  await page.locator('input[name="p_dates"]').click();
  await page.locator('.datepicker-days td.day:not(.old):not(.new):not(.disabled)').first().click();
  await expect(page.locator('input[name="p_dates"]')).not.toHaveValue('');
  await expect(page.locator('input[name="p_shift_time"]')).not.toHaveValue('');

  // The grid asks for one software and one service. `force`, because Bootstrap
  // 4's custom checkbox hides the real input behind its label.
  await page.locator('input[name="p_skills[]"]').first().check({ force: true });
  await page.locator('input[name="p_services[]"]').first().check({ force: true });

  await page.locator('[name="savepostjob"]').click();
  await settle(page);

  const posted = scalar(
    `SELECT p_id FROM post_job WHERE p_store_id = ${branch} AND u_id = ${user.manager} ORDER BY p_id DESC LIMIT 1;`,
  );

  expect(posted, 'the shift was posted').not.toBe('');

  // The mysql client prints a NULL column as the word NULL.
  expect(
    scalar(`SELECT p_hourly_rate FROM post_job WHERE p_id = ${posted};`),
    'with no rate on it',
  ).toBe('NULL');
});

test('the details panel shows the rate to the owner, not to the manager', async ({ page }) => {
  seedShifts();

  await loginAsFrontUser(page, OWNER);
  await page.goto('employer/all_jobs');
  await settle(page);
  await page.locator('#joblist tbody tr', { hasText: 'E2E-RATE-SHIFT' }).getByRole('button', { name: 'View' }).click();

  await expect(page.locator('#modalShiftRate')).toBeVisible();
  await expect(page.locator('#modalShiftRate')).toHaveValue(`$${RATE}`);

  // Every box in the panel only reports; none of them takes a keystroke. This
  // is what the empty-looking Shift Date and Shift Time boxes used to invite.
  for (const id of ['#modalShiftFor', '#modalShiftDate', '#modalShiftTime', '#modalShiftRate']) {
    await expect(page.locator(id), `${id} is read-only`).toHaveAttribute('readonly', /.*/);
  }

  await page.goto('employer/logout');
  await loginAsFrontUser(page, MANAGER);
  await page.goto('employer/all_jobs');
  await settle(page);

  const row = page.locator('#joblist tbody tr', { hasText: 'E2E-RATE-SHIFT' });
  await expect(row, "the manager sees their branch's shift").toHaveCount(1);

  // Not merely hidden: the rate is not in the page at all, and neither is the
  // data attribute the panel is filled from.
  await expect(page.locator('#modalShiftRate')).toHaveCount(0);
  expect(await page.content(), 'no rate anywhere in the source').not.toContain('data-shift_rate');

  await row.getByRole('button', { name: 'View' }).click();
  await expect(page.locator('#viewModal')).toBeVisible();
  await expect(page.locator('#viewModal')).not.toContainText('Shift Rate');
  await expectNoServerError(page);
});

/**
 * Book the fixture's shift, with the note the administrator left on it.
 *
 * Any active pharmacist will do - the panel names whoever is on the shift, and
 * this test is about the note beside the name rather than the name.
 *
 * @param {number} pid post_job.p_id
 */
function bookWithMessage(pid) {
  const applicant = scalar('SELECT u_id FROM users WHERE u_usertype = 2 AND u_status = 1 LIMIT 1;');

  expect(applicant, 'a pharmacist to put on the shift').not.toBe('');

  query(`
    INSERT INTO stu_saved_applied_jobs
      (u_id, agency_id, p_id, sj_applied_date, sj_accept_date, sj_status, sj_is_approved,
       sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments, created, modified)
    VALUES (${applicant}, ${user.owner}, ${pid}, NOW(), NOW(), 6, 1, '${MESSAGE}', '', '', 0, NOW(), NOW());
  `);
  query(`UPDATE post_job SET p_status = 1, p_approved = 3 WHERE p_id = ${pid};`);
}

test('the details panel shows the booking message to the owner, not to the manager', async ({ page }) => {
  bookWithMessage(seedShifts());

  await loginAsFrontUser(page, OWNER);
  await page.goto('employer/all_jobs');
  await settle(page);
  await page.locator('#joblist tbody tr', { hasText: 'E2E-RATE-SHIFT' }).getByRole('button', { name: 'View' }).click();

  await expect(page.locator('#modalMessage')).toBeVisible();
  await expect(page.locator('#modalMessage')).toHaveValue(MESSAGE);

  await page.goto('employer/logout');
  await loginAsFrontUser(page, MANAGER);
  await page.goto('employer/all_jobs');
  await settle(page);

  const row = page.locator('#joblist tbody tr', { hasText: 'E2E-RATE-SHIFT' });
  await expect(row, "the manager sees their branch's shift").toHaveCount(1);

  // Gone from the page rather than hidden in it: the note names what the shift
  // was agreed at, which is the same thing the rate is.
  await expect(page.locator('#modalMessage')).toHaveCount(0);
  expect(await page.content(), 'no note anywhere in the source').not.toContain(MESSAGE);

  await row.getByRole('button', { name: 'View' }).click();
  await expect(page.locator('#viewModal')).toBeVisible();
  await expect(page.locator('#viewModal')).not.toContainText('Message');
  await expectNoServerError(page);
});
