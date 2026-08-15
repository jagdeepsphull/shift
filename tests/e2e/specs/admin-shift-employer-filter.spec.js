// @ts-check
/**
 * The User Type picker on the admin shift form, which narrows Choose Employer
 * to one `employerKinds` code.
 *
 * The filtering happens in the browser over options already in the page, so
 * these drive the real select rather than checking a request.
 *
 * The store form carried the same pair and no longer does: a location belongs
 * to an owner, and the store a manager runs is assigned on the employer form
 * instead - so that form lists owners alone and has nothing left to narrow.
 * The test below holds it to that.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

/** employerKinds code -> the SQL that defines it, in step with Config\AppSettings. */
const KINDS = {
  1: 'u_emp_role = 1',
  2: 'u_emp_role = 2',
};

const employer = (name, role, parent) => `
  INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname,
                     u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo,
                     u_provice, u_city, u_address1, u_pincode, u_phone, u_email, u_terms, u_status,
                     u_collartype, created, modified, u_login_attempt, u_login_attempt_dt,
                     u_ipaddress, reset_token, token_expiry)
  VALUES (1, 0, ${role}, ${parent}, '${name}@e2e.test', 'E2E', 'Emp', MD5('x'), '${name}',
          0, '', '', '', 1, 1, '', '', '0000000000', '${name}@e2e.test', 1, 1, 0,
          NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');`;

const cleanup = () => {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-EMPFILTER-%';");
  query("DELETE FROM store WHERE s_name LIKE 'E2E Empfilter%';");
  query("DELETE FROM users WHERE u_comp_name LIKE 'E2E Empfilter%';");
};

test.beforeEach(async ({ page }) => {
  cleanup();

  // One employer of each kind, so every filter has something to find whatever
  // the live data happens to hold.
  const parent = scalar('SELECT u_id FROM users WHERE u_usertype = 1 AND u_emp_role = 1 LIMIT 1;');
  query(employer('E2E Empfilter Owner', 1, 0));
  query(employer('E2E Empfilter Manager', 2, parent || 1));

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

test('the type picker sits before Choose Employer and offers every kind', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  const ids = await page.locator('.card-body select').evaluateAll((els) =>
    els.map((e) => e.id).filter(Boolean));
  expect(ids.slice(0, 3)).toEqual(['u_emp_kind', 'u_id', 'p_store_id']);

  await expect(page.locator('#u_emp_kind option')).toHaveText([
    /All Employers/,
    /Owners/,
    /Managers/,
  ]);

  // It only filters - posting it would put a value on the shift nothing reads.
  await expect(page.locator('#u_emp_kind')).not.toHaveAttribute('name', /./);
  await expectNoServerError(page);
});

test('each type narrows the employer list to exactly that kind', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  const shown = async () => (await page.locator('#u_id option').count()) - 1; // less the placeholder
  const all = await shown();

  for (const [code, where] of Object.entries(KINDS)) {
    await page.selectOption('#u_emp_kind', code);

    const expected = Number(
      scalar(`SELECT COUNT(*) FROM users WHERE u_usertype = 1 AND u_status = 1 AND ${where};`),
    );

    expect(await shown(), `code ${code} count`).toBe(expected);
    await expect(page.locator('#u_id'), `code ${code} seeded row`)
      .toContainText(code === '2' ? 'E2E Empfilter Manager' : 'E2E Empfilter Owner');
  }

  // Back to All restores everyone, including accounts from before the kinds
  // existed, which carry role 0 - the reason that entry exists.
  await page.selectOption('#u_emp_kind', '');
  expect(await shown()).toBe(all);
  await expectNoServerError(page);
});

test('switching to a type the chosen employer is not clears the choice', async ({ page }) => {
  const owner = scalar("SELECT u_id FROM users WHERE u_comp_name = 'E2E Empfilter Owner';");

  await page.goto('sadmin/postjobs/add');
  await page.selectOption('#u_emp_kind', '1');
  await page.selectOption('#u_id', String(owner));
  await settle(page);

  await page.selectOption('#u_emp_kind', '2');

  // Neither the employer nor the store list it loaded may survive - the form
  // would otherwise post an employer no longer on offer.
  await expect(page.locator('#u_id')).toHaveValue('');
  await expect(page.locator('#p_store_id option')).toHaveCount(1);
  await expectNoServerError(page);
});

test('the store form asks for an owner only, and offers no managers', async ({ page }) => {
  await page.goto('sadmin/stores/add');

  // A store belongs to an owner, so there is nothing here for a type picker to
  // narrow - the one it used to carry is gone, and the first select on the
  // form is the owner itself.
  const ids = await page.locator('.card-body select').evaluateAll((els) =>
    els.map((e) => e.id).filter(Boolean));
  expect(ids[0]).toBe('store_owner');
  await expect(page.locator('#store_owner_kind')).toHaveCount(0);

  await expect(page.locator('.form-group', { has: page.locator('#store_owner') }).locator('label'))
    .toHaveText('Owner');

  // Which store a manager runs is set on the employer form, where they are
  // assigned one of their group's - they never own one, so they are not asked
  // for here.
  await expect(page.locator('#store_owner')).toContainText('E2E Empfilter Owner');
  await expect(page.locator('#store_owner')).not.toContainText('E2E Empfilter Manager');

  // Every employer that is not a manager is listed, active or not - unlike the
  // shift form, which only offers active ones. Accounts from before the kinds
  // carry role 0 and own their locations, so they are on it too.
  const shown = (await page.locator('#store_owner option').count()) - 1; // less the placeholder
  expect(shown).toBe(Number(
    scalar('SELECT COUNT(*) FROM users WHERE u_usertype = 1 AND u_emp_role != 2;'),
  ));

  await expectNoServerError(page);
});

test('the edit screen opens on its own shift employer kind', async ({ page }) => {
  const owner = scalar("SELECT u_id FROM users WHERE u_comp_name = 'E2E Empfilter Owner';");

  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_status, created, modified)
         VALUES (${owner}, 'E2E Empfilter Store', '1',
                 (SELECT p_id FROM province ORDER BY p_id LIMIT 1),
                 (SELECT c_id FROM city ORDER BY c_id LIMIT 1),
                 'x', 'x', '0000000000', 1, NOW(), NOW());`);
  const store = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Empfilter Store';");
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  query(`INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
       p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES (${owner}, ${store}, 'E2E Empfilter Co', 'E2E-EMPFILTER-1', 0,
       (SELECT p_id FROM province ORDER BY p_id LIMIT 1),
       (SELECT c_id FROM city ORDER BY c_id LIMIT 1),
       ${shiftFor}, 30, 30, '01-09-2027', '2027-09-01', '09:00 - 17:00', '', '',
       'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());`);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-EMPFILTER-1';");

  await page.goto(`sadmin/postjobs/edit/${shift}`);

  // Opening on "All Employers" over an already-narrowed choice would misread.
  await expect(page.locator('#u_emp_kind')).toHaveValue('1');

  // And narrowing on load must not disturb what the shift already holds.
  await expect(page.locator('#u_id')).toHaveValue(String(owner));
  await expect(page.locator('#p_store_id')).toHaveValue(String(store));
  await expectNoServerError(page);
});
