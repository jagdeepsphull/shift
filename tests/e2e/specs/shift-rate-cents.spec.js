// @ts-check
/**
 * Hourly rates carry cents.
 *
 * Both rate columns were `int` and both boxes were `type="number"` with the
 * default step of 1, so $42.50 could not be typed and could not have been
 * stored if it had been. Pharmacy rates are not whole dollars, so the column is
 * DECIMAL(6,2) now and the boxes step by a cent - see
 * app/Database/Migrations/2026-09-02-090000_ShiftRatesTakeCents.php.
 *
 * What is checked is the round trip, not the attribute: a step the browser
 * accepts is worth nothing if the column rounds the cents off on the way in.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');
const { pickShiftStore } = require('../helpers/stores');
const { loginAsFrontUser } = require('../helpers/front');
const { csrfHeaders } = require('../helpers/csrf');

/** The employer whose own shift form is checked at the end. */
const EMPLOYER = { user: 'cents@e2e.test', pass: 'E2eTest@12345' };

const ids = {};

const cleanup = () => {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-CENTS-%' OR p_company_name = 'E2E Cents Co';");
  query("DELETE FROM store WHERE s_name LIKE 'E2E Cents%';");
  query("DELETE FROM users WHERE u_userid LIKE 'cents%@e2e.test';");
};

test.beforeEach(async ({ page }) => {
  cleanup();

  ids.city = scalar('SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1;');
  ids.province = scalar(`SELECT c_province FROM city WHERE c_id = ${ids.city};`);
  ids.shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_id LIMIT 1;');
  ids.skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 ORDER BY ss_id LIMIT 1;');
  ids.service = scalar('SELECT st_id FROM store_service WHERE st_status = 1 ORDER BY st_id LIMIT 1;');

  query(`
    INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname,
                       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo,
                       u_provice, u_city, u_address1, u_pincode, u_phone, u_email, u_terms, u_status,
                       u_collartype, created, modified, u_login_attempt, u_login_attempt_dt,
                       u_ipaddress, reset_token, token_expiry)
    VALUES (1, 0, 1, 0, 'cents@e2e.test', 'E2E', 'Cents', MD5('E2eTest@12345'), 'E2E Cents Chain', 0, '', '', '',
            ${ids.province}, ${ids.city}, '', '', '0000000000', 'cents@e2e.test', 1, 1, 0, NOW(), NOW(), 0,
            NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);
  ids.employer = scalar("SELECT u_id FROM users WHERE u_userid = 'cents@e2e.test';");

  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${ids.employer}, 'E2E Cents Branch', 'C-1', ${ids.province}, ${ids.city}, 'x', 'x',
                 '0000000000', '${ids.skill}', '${ids.service}', '', 1, NOW(), NOW());`);
  ids.store = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Cents Branch';");

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

/**
 * A shift on file, for the screens that edit rather than create one.
 *
 * `pending` is status and approval both 0, which is the only state the
 * employer's own edit form will open - see Employer::shiftInScope(). The back
 * office edits a shift in any state, so its tests use the approved one.
 */
const seedShift = (title, pending = false) => {
  const state = pending ? '0, 0' : '1, 1';

  query(`INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
       p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES (${ids.employer}, ${ids.store}, 'E2E Cents Co', '${title}', 0,
       ${ids.province}, ${ids.city}, ${ids.shiftFor}, 30, 30, '01-09-2027', '2027-09-01',
       '09:00 - 17:00', '${ids.skill}', '${ids.service}', 'Seeded by the end-to-end suite.',
       0, ${state}, NOW(), NOW());`);

  return scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${title}';`);
};

test('both rate columns hold two decimal places', () => {
  for (const column of ['p_hourly_rate', 'p_ac_hourly_rate']) {
    const type = scalar(
      "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'post_job'" +
        ` AND COLUMN_NAME = '${column}' AND TABLE_SCHEMA = DATABASE();`,
    );

    // Not float: a rate is money, and 42.10 has no exact binary fraction.
    expect(type, `${column} keeps cents`).toMatch(/^decimal\(\d+,2\)$/);
  }
});

test('a shift can be added at a rate with cents', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  // A whole-cent step, or the browser refuses the value before it is posted.
  await expect(page.locator('input[name="p_hourly_rate"]')).toHaveAttribute('step', '0.01');
  await expect(page.locator('input[name="p_ac_hourly_rate"]')).toHaveAttribute('step', '0.01');

  await pickShiftStore(page, ids.store);
  await expect.poll(() => page.locator('#cbg_p_skills input:checked').count()).toBeGreaterThan(0);

  await page.selectOption('select[name="p_shift_for"]', String(ids.shiftFor));
  await page.fill('input[name="p_hourly_rate"]', '42.55');
  await page.fill('input[name="p_ac_hourly_rate"]', '38.25');
  await page.evaluate(() => {
    window.jQuery('input[name="p_dates"]').datepicker('setDate', new Date(2027, 8, 1));
  });

  await settle(page);
  await Promise.all([page.waitForLoadState('load'), page.click('input[name="savedata"]')]);
  await settle(page);
  await expectNoServerError(page);

  const shift = scalar(`SELECT p_id FROM post_job WHERE p_store_id = ${ids.store} ORDER BY p_id DESC LIMIT 1;`);
  expect(shift, 'the shift was saved').not.toBe('');

  // The cents survived the column, which an int would have rounded away.
  expect(scalar(`SELECT p_hourly_rate FROM post_job WHERE p_id = ${shift};`)).toBe('42.55');
  expect(scalar(`SELECT p_ac_hourly_rate FROM post_job WHERE p_id = ${shift};`)).toBe('38.25');

  query(`DELETE FROM post_job WHERE p_id = ${shift};`);
});

test('the edit screen shows the cents and saves a new one', async ({ page }) => {
  const shift = seedShift('E2E-CENTS-1');
  query(`UPDATE post_job SET p_hourly_rate = 42.55, p_ac_hourly_rate = 38.25 WHERE p_id = ${shift};`);

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await expectNoServerError(page);

  // What is on file is what the box opens on - a rate that reads back short
  // would be silently changed by the next save.
  await expect(page.locator('input[name="p_hourly_rate"]')).toHaveValue('42.55');
  await expect(page.locator('input[name="p_ac_hourly_rate"]')).toHaveValue('38.25');

  await page.fill('input[name="p_hourly_rate"]', '19.99');
  await page.fill('input[name="p_ac_hourly_rate"]', '17.05');

  await settle(page);
  await Promise.all([page.waitForLoadState('load'), page.click('input[name="savedata"]')]);
  await settle(page);
  await expectNoServerError(page);

  expect(scalar(`SELECT p_hourly_rate FROM post_job WHERE p_id = ${shift};`)).toBe('19.99');
  expect(scalar(`SELECT p_ac_hourly_rate FROM post_job WHERE p_id = ${shift};`)).toBe('17.05');
});

test('the employer can set a rate with cents on their own form', async ({ browser }) => {
  // The rate an employer sets is the same column. Leaving their form on the
  // default step would have the browser refuse a rate the back office can set,
  // and refuse to re-save a shift that already carries one.
  const shift = seedShift('E2E-CENTS-2', true);
  query(`UPDATE post_job SET p_hourly_rate = 42.55 WHERE p_id = ${shift};`);

  // Their own session, not the administrator's: the two sign in at different
  // doors and the employer portal turns an admin away.
  const context = await browser.newContext();
  const page = await context.newPage();

  await loginAsFrontUser(page, EMPLOYER);

  await page.goto('employer/post_job');
  await expect(page.locator('input[name="p_hourly_rate"]')).toHaveAttribute('step', '0.01');

  await page.goto(`employer/edit_job/${shift}`);
  await expect(page.locator('input[name="p_hourly_rate"]')).toHaveValue('42.55');

  await page.fill('input[name="p_hourly_rate"]', '31.75');
  await Promise.all([page.waitForLoadState('load'), page.click('[name="savepostjob"]')]);
  await expectNoServerError(page);

  expect(scalar(`SELECT p_hourly_rate FROM post_job WHERE p_id = ${shift};`)).toBe('31.75');

  await context.close();
});

test('the box refuses a second decimal point and a third decimal digit', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  const box = page.locator('input[name="p_hourly_rate"]');

  // A number input holds "3.4.3.4" as text while reporting its value as '', so
  // the box looks filled in and posts nothing. Every point after the first is
  // dropped and its digits keep their place, then the cents are cut to two.
  await box.click();
  await page.keyboard.type('3.4.3.4.4');
  await expect(box).toHaveValue('3.43');

  // A rate starts with dollars: ".334" is not an amount of money.
  await box.fill('');
  await box.click();
  await page.keyboard.type('.334');
  await expect(box).toHaveValue('334');

  // And there are only ever two cents.
  await box.fill('');
  await box.click();
  await page.keyboard.type('42.555');
  await expect(box).toHaveValue('42.55');
});

test('the server refuses a rate no browser would have posted', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  const headers = await csrfHeaders(page);
  const count = () => Number(scalar(`SELECT COUNT(*) FROM post_job WHERE p_store_id = ${ids.store};`));

  const post = (rate) => page.request.post('sadmin/postjobs/add', {
    headers,
    form: {
      p_store_id: String(ids.store),
      p_shift_for: String(ids.shiftFor),
      p_dates: '01-09-2027',
      p_shift_time: '09:00 - 17:00',
      p_hourly_rate: rate,
      p_ac_hourly_rate: '40',
      // Not about the rate, but the column is NOT NULL and the controller
      // writes the post value straight through - a payload without it is a
      // 500 rather than a refusal, which would make this test pass on the
      // wrong evidence.
      p_jobinfo: 'Posted by the end-to-end suite.',
      savedata: 'Save',
    },
  });

  // The form's step and range are a suggestion to a browser and nothing at all
  // to anything else, which is what these post as.
  const refused = [
    '.334',            // no dollars in front of the point
    '42.555',          // a third decimal, which the column would round away
    '3.4.3.4',         // a point in every gap
    '3434.343..4.34',  // the same, pasted
    'forty',           // not a number
    '5',               // under the minimum the form shows
    '500',             // over the maximum
  ];

  for (const rate of refused) {
    const before = count();

    // 200, not 500: the form comes back with the rule's message on it. A
    // crash would leave the row count alone too, and would prove nothing.
    const response = await post(rate);

    expect(response.status(), `"${rate}" is refused, not fatal`).toBe(200);
    expect(count(), `"${rate}" is not a rate`).toBe(before);
  }

  // The control: the same request with a real rate does save, so the six above
  // were refused by the rule and not by something else about the request.
  const before = count();
  await post('42.55');
  expect(count(), 'a real rate still saves').toBe(before + 1);

  const saved = scalar(`SELECT p_id FROM post_job WHERE p_store_id = ${ids.store} ORDER BY p_id DESC LIMIT 1;`);
  expect(scalar(`SELECT p_hourly_rate FROM post_job WHERE p_id = ${saved};`)).toBe('42.55');

  query(`DELETE FROM post_job WHERE p_id = ${saved};`);
});
