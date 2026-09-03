// @ts-check
/**
 * Store Number on the two back-office shift screens.
 *
 * Manage Shifts and Job Applications each name the branch a shift is at by its
 * address, which answers "where?" but not "which one?" - a chain's two shops on
 * the same high street read alike, and the number on the chain's own books is
 * what the office says down the phone and types into a rota. So the number sits
 * beside the address on both lists, and like every other column there it can be
 * switched off from the Column visibility menu when it is not wanted.
 *
 * The value comes from `store.s_number` through shiftStore(), which falls back
 * to the employer's licence number for a shift raised before the stores
 * existed - both cases are seeded here, because the fallback is what the older
 * half of a real list is made of.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

/** On the branch's own books, and on the login of the shift with no branch. */
const STORE_NUMBER = 'E2E-STORENO-77';
const LICENCE_NUMBER = 'E2E-STORENO-88';

const WITH_STORE = 'E2E-STORENO-WITH-STORE';
const NO_STORE = 'E2E-STORENO-NO-STORE';

/**
 * The columns each list is made of, in the order the Column visibility menu
 * offers them - which is the order they are written in the view. Column 0 is
 * the record id, hidden by the shared table script and kept off that menu with
 * it, so it is not here.
 */
const SHIFT_COLUMNS = [
  'Shift ID',
  'Store Address',
  'Store Number',
  'Applicant type',
  'Applicant',
  'Lic. No.',
  'Shift Date',
  'Shift Time',
  'Shift Status',
  'Action',
];

const APPLICATION_COLUMNS = [
  'Shift ID',
  'Store Address',
  'Store Number',
  'Book Shift For',
  'Lic. No.',
  'Shift Requested For',
  'Shift Date',
  'Shift Time',
  'Approval Status',
  'Action',
];

const ids = {};

function removeFixture() {
  // The applications first - they are the rows pointing at the shifts. MySQL
  // will not let a subquery in a DELETE read the table being deleted from
  // without the extra derived table around it.
  query(`
    DELETE FROM stu_saved_applied_jobs
     WHERE p_id IN (SELECT p_id FROM (SELECT p_id FROM post_job WHERE p_job_title LIKE 'E2E-STORENO-%') x);
  `);
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-STORENO-%';");
  query("DELETE FROM store WHERE s_name = 'E2E Storeno Branch';");
  query("DELETE FROM users WHERE u_userid = 'storeno@e2e.test';");
}

test.beforeAll(() => {
  removeFixture();

  ids.city = scalar('SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1;');
  ids.province = scalar(`SELECT c_province FROM city WHERE c_id = ${ids.city};`);
  ids.shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_id LIMIT 1;');

  // Whoever applies for the two shifts. The applications screen inner-joins the
  // employer but only left-joins the applicant, so a site with no pharmacist on
  // it still gets its rows - they just show a blank name.
  ids.applicant = scalar('SELECT u_id FROM users WHERE u_usertype = 2 AND u_status = 1 LIMIT 1;');

  query(`
    INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname,
                       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo,
                       u_provice, u_city, u_address1, u_pincode, u_phone, u_email, u_terms, u_status,
                       u_collartype, created, modified, u_login_attempt, u_login_attempt_dt,
                       u_ipaddress, reset_token, token_expiry)
    VALUES (1, 0, 1, 0, 'storeno@e2e.test', 'E2E', 'Storeno', MD5('x'), 'E2E Storeno Chain', 0,
            '${LICENCE_NUMBER}', '', '', ${ids.province}, ${ids.city}, '9 Login Lane', 'M5A 1A1',
            '4160000077', 'storeno@e2e.test', 1, 1, 0, NOW(), NOW(), 0, NOW(), '127.0.0.1', '',
            '1970-01-01 00:00:00');
  `);
  ids.employer = scalar("SELECT u_id FROM users WHERE u_userid = 'storeno@e2e.test';");

  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone,
                       s_skills, s_services, s_additional_details, s_status, created, modified)
    VALUES (${ids.employer}, 'E2E Storeno Branch', '${STORE_NUMBER}', ${ids.province}, ${ids.city},
            '77 Branch Road', 'M5A 1A1', '4160000078', '', '', '', 1, NOW(), NOW());
  `);
  ids.store = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Storeno Branch';");

  // The same employer twice over: one shift at the branch, one raised before
  // there were branches. Dated years out so no expiry job can take them off the
  // list mid-run.
  for (const shift of [{ title: WITH_STORE, store: ids.store }, { title: NO_STORE, store: 0 }]) {
    query(`
      INSERT INTO post_job
        (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
         p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
         p_jobinfo, p_featured, p_status, p_approved, created, modified)
      VALUES (${ids.employer}, ${shift.store}, 'E2E Storeno Chain', '${shift.title}', 0,
         ${ids.province}, ${ids.city}, ${ids.shiftFor}, 30, 30, '17-06-2031', '2031-06-17',
         '09:00 - 17:00', '', '', 'Seeded by the end-to-end suite.', 0, 1, 0, NOW(), NOW());
    `);

    query(`
      INSERT INTO stu_saved_applied_jobs
        (u_id, agency_id, p_id, sj_applied_date, sj_status, sj_is_approved, sj_admin_comment,
         sj_applied_desc, sj_resubmit_comments, sj_rejected_comments, created, modified)
      VALUES (${ids.applicant}, ${ids.employer},
         (SELECT p_id FROM post_job WHERE p_job_title = '${shift.title}' LIMIT 1),
         NOW(), 1, 0, '', '', '', 0, NOW(), NOW());
    `);
  }
});

test.afterAll(() => {
  removeFixture();
});

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

/**
 * The one row for `title`, found by filtering: both lists run to every shift
 * ever raised, and a seeded one is nowhere near the first page.
 */
async function fixtureRow(page, title) {
  await filterTable(page, title);
  await settle(page);

  return page.locator('#example1 tbody tr', { hasText: title });
}

test('the shift list carries Store Number, and offers it to hide', async ({ page }) => {
  await page.goto('sadmin/postjobs');
  await expectNoServerError(page);

  await expect(page.locator('#example1 thead th')).toHaveText(SHIFT_COLUMNS);
  await expect(page.locator('#example1 tfoot th')).toHaveText(SHIFT_COLUMNS);

  await page.locator('button.buttons-colvis').click();
  await expect(page.locator('.dt-button-collection .dt-button')).toHaveText(SHIFT_COLUMNS);
});

test('the applications list carries Store Number, and offers it to hide', async ({ page }) => {
  await page.goto('sadmin/applications');
  await expectNoServerError(page);

  await expect(page.locator('#example1 thead th')).toHaveText(APPLICATION_COLUMNS);
  await expect(page.locator('#example1 tfoot th')).toHaveText(APPLICATION_COLUMNS);

  await page.locator('button.buttons-colvis').click();
  await expect(page.locator('.dt-button-collection .dt-button')).toHaveText(APPLICATION_COLUMNS);
});

test('a shift shows the number of the branch it is at, on both screens', async ({ page }) => {
  for (const path of ['sadmin/postjobs', 'sadmin/applications']) {
    await page.goto(path);
    await expectNoServerError(page);

    const row = await fixtureRow(page, WITH_STORE);
    await expect(row, `${path}: the branch's own number`).toContainText(STORE_NUMBER);
    await expect(row, `${path}: and its address beside it`).toContainText('77 Branch Road');
  }
});

test('a shift with no branch falls back to the employer licence number', async ({ page }) => {
  for (const path of ['sadmin/postjobs', 'sadmin/applications']) {
    await page.goto(path);
    await expectNoServerError(page);

    const row = await fixtureRow(page, NO_STORE);
    await expect(row, `${path}: the number off the login`).toContainText(LICENCE_NUMBER);
  }
});
