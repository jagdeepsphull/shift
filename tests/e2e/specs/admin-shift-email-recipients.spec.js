// @ts-check
/**
 * "Send shift e-mail to" on the two back-office shift forms.
 *
 * A shift going live announces itself to the pharmacy, and this is where the
 * administrator says which side of the store hears about it: the owner, the
 * manager who runs the branch, both, or neither.
 *
 * Neither is a real answer - some shifts are arranged by phone - and the shift
 * is still announced to the site's own configured address, which is on every
 * one of these e-mails and shown on the form as a recipient nobody can untick.
 * Neither is the case worth pinning: an empty pair of boxes has to survive a
 * save, or the next edit quietly re-ticks them and writes to a pharmacy that
 * asked not to be written to. Who the addresses resolve to is tested directly
 * in tests/unit/ShiftEmailRecipientsTest.php; what is under test here is the
 * form.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');
const { pickShiftStore } = require('../helpers/stores');

const ids = {};

const OWNER_BOX = 'input[name="p_email_to[]"][value="owner"]';
const MANAGER_BOX = 'input[name="p_email_to[]"][value="manager"]';
const SITE_ROW = '#p_email_to_site';

/**
 * Toggle one of the boxes the way somebody using the screen does.
 *
 * AdminLTE's custom-control draws the tick itself and leaves the real input
 * underneath the label, so a click aimed at the input never lands - the label
 * is what takes it, and what a person clicks.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} side
 */
const toggle = (page, side) => page.click(`label[for="p_email_to_${side}"]`);

const cleanup = () => {
  query(`DELETE pj FROM post_job pj
         JOIN users u ON u.u_id = pj.u_id
         WHERE u.u_userid LIKE 'mailto%@e2e.test';`);
  query("DELETE FROM store WHERE s_name LIKE 'E2E MailTo%';");
  query("DELETE FROM users WHERE u_userid LIKE 'mailto%@e2e.test';");
};

test.beforeAll(() => {
  cleanup();

  ids.city = scalar('SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1;');
  ids.province = scalar(`SELECT c_province FROM city WHERE c_id = ${ids.city};`);
  ids.skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 ORDER BY ss_id LIMIT 1;');
  ids.service = scalar('SELECT st_id FROM store_service WHERE st_status = 1 ORDER BY st_id LIMIT 1;');
  ids.shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_id LIMIT 1;');

  const user = (role, login, storeId) => query(
    `INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid,
                        u_fname, u_lname, u_pass, u_comp_name, u_l_provice, u_licence_no,
                        u_company_logo, u_photo, u_provice, u_city, u_address1, u_pincode, u_phone,
                        u_email, u_terms, u_status, u_collartype, created, modified,
                        u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
     VALUES (1, 0, ${role}, 0, ${storeId}, '${login}', 'E2E', 'MailTo', MD5('x'), 'E2E MailTo Chain',
             0, '', '', '', ${ids.province}, ${ids.city}, 'x', 'x', '0000000000', '${login}', 1, 1, 0,
             NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');`,
  );

  user(1, 'mailtoowner@e2e.test', 0);
  ids.owner = scalar("SELECT u_id FROM users WHERE u_userid = 'mailtoowner@e2e.test';");

  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${ids.owner}, 'E2E MailTo Store', '1', ${ids.province}, ${ids.city}, 'x', 'x',
                 '0000000000', '${ids.skill}', '${ids.service}', '', 1, NOW(), NOW());`);
  ids.store = scalar("SELECT s_id FROM store WHERE s_name = 'E2E MailTo Store';");

  // The manager account that runs that branch - the second side of the store,
  // and the reason there are two boxes rather than one.
  user(2, 'mailtomanager@e2e.test', ids.store);
});

test.afterAll(cleanup);

/**
 * Fill everything the add form insists on, leaving the e-mail boxes alone.
 *
 * @param {import('@playwright/test').Page} page
 */
async function fillShift(page) {
  await page.goto('sadmin/postjobs/add');
  await pickShiftStore(page, ids.store);

  // The store's defaults tick the required groups; wait for them rather than
  // ticking by hand, or the submit races the fill.
  await expect.poll(() => page.locator('#cbg_p_skills input:checked').count()).toBeGreaterThan(0);

  await page.selectOption('select[name="p_shift_for"]', String(ids.shiftFor));
  await page.fill('input[name="p_hourly_rate"]', '40');
  await page.fill('input[name="p_ac_hourly_rate"]', '45');
  await page.evaluate(() => {
    window.jQuery('input[name="p_dates"]').datepicker('setDate', new Date(2027, 8, 2));
  });
  await expect(page.locator('input[name="p_dates"]')).toHaveValue('02-09-2027');
  await expect(page.locator('input[name="p_shift_time"]')).not.toHaveValue('');
}

const submit = async (page) => {
  await page.click('input[name="savedata"], button[name="savedata"]');
  await settle(page);
};

/** What the seeded employer's one shift says to do. */
const storedChoice = () => scalar(`SELECT p_email_to FROM post_job WHERE u_id = ${ids.owner};`);
const seededShift = () => scalar(`SELECT p_id FROM post_job WHERE u_id = ${ids.owner};`);

test('the add form offers both sides of the store, ticked', async ({ page }) => {
  await loginAsAdmin(page);
  await fillShift(page);

  // Both, because a new shift telling nobody is a decision rather than a
  // default - the administrator has to untick to make it.
  await expect(page.locator(OWNER_BOX)).toBeChecked();
  await expect(page.locator(MANAGER_BOX)).toBeChecked();
  await expectNoServerError(page);
});

test('the configured address is shown as a recipient nobody can change', async ({ page }) => {
  await loginAsAdmin(page);
  await fillShift(page);

  const site = page.locator(SITE_ROW);

  // Ticked and disabled, because it is a statement rather than a choice: the
  // e-mail goes there whatever the two boxes above it say.
  await expect(site).toBeChecked();
  await expect(site).toBeDisabled();
  await expect(page.locator(`label[for="p_email_to_site"]`)).toContainText('@');

  // And it stays out of the answer - a disabled input is not posted, so it can
  // never end up in p_email_to next to owner and manager.
  await expect(site).not.toHaveAttribute('name', /.+/);
  await expectNoServerError(page);
});

test('unticking one side saves only the other', async ({ page }) => {
  await loginAsAdmin(page);
  await fillShift(page);

  await toggle(page, 'manager');
  await expect(page.locator(MANAGER_BOX)).not.toBeChecked();
  await submit(page);
  await expectNoServerError(page);

  expect(storedChoice(), 'the shift records the one side that was ticked').toBe('owner');
});

test('the edit form opens on what the shift says, and lets both go', async ({ page }) => {
  const pid = seededShift();
  test.skip(!pid, 'the add test did not leave a shift behind');

  await loginAsAdmin(page);
  await page.goto(`sadmin/postjobs/edit/${pid}`);
  await settle(page);

  await expect(page.locator(OWNER_BOX), 'it opens on the saved choice').toBeChecked();
  await expect(page.locator(MANAGER_BOX)).not.toBeChecked();

  // Neither: the shift is arranged off the site and the pharmacy is not to be
  // written to. It has to survive the save, and it is the one state a form that
  // simply skipped an empty field would lose.
  await toggle(page, 'owner');
  await expect(page.locator(OWNER_BOX)).not.toBeChecked();
  await submit(page);
  await expectNoServerError(page);

  expect(storedChoice(), 'neither side is stored as neither, not as the default').toBe('');

  await page.goto(`sadmin/postjobs/edit/${pid}`);
  await settle(page);

  await expect(page.locator(OWNER_BOX), 'and it is still neither on the way back').not.toBeChecked();
  await expect(page.locator(MANAGER_BOX)).not.toBeChecked();
});
