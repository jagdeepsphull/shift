// @ts-check
/**
 * Manage Email: the list of every account, the per-user permissions page, and
 * the enforcement itself.
 *
 * The stored value is the INVERSE of the checkboxes - `u_email_blocked` holds
 * what is withheld, so a blank row receives everything. The round-trip test
 * pins that inversion, because getting it backwards would silently mute every
 * e-mail for every user while the screen showed all boxes ticked.
 *
 * The boxes on offer are the e-mails that account can actually be sent, not the
 * whole config: an applicant is never told a shift of theirs is live and never
 * gets the employer's half of a booking, and an employer is never sent the
 * applicant's half or the day-before reminder. The screen used to offer all six
 * to everybody, so half of any one page governed mail that could not arrive
 * whichever way it was left.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

const APPLICANT = 'e2e.mailperm@example.com';
const EMPLOYER = 'e2e.mailperm.emp@example.com';

/** The labels in AppSettings::$emailTypes, by audience. */
const EMPLOYER_ONLY = ['Shift posted', 'Booking confirmation (as employer)'];
const APPLICANT_ONLY = ['Booking confirmation (as applicant)', 'Day-before shift reminder'];
const BOTH = ['Welcome', 'Account approved'];

const cleanup = () => {
  query(`DELETE FROM users WHERE u_email IN ('${APPLICANT}', '${EMPLOYER}');`);
};

/** @param {string} email @param {number} usertype @param {number} role */
const seedUser = (email, usertype, role) => query(`
  INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname,
                     u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo,
                     u_provice, u_city, u_address1, u_pincode, u_phone, u_email, u_email_blocked,
                     u_terms, u_status, u_collartype, created, modified, u_login_attempt,
                     u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
  VALUES (${usertype}, ${usertype === 2 ? 1 : 0}, ${role}, 0, '${email}', 'Mail', 'Perm', MD5('x'),
          '${usertype === 1 ? 'E2E Mailperm Co' : ''}', 0, '', '', '',
          1, 1, '', '', '0000000000', '${email}', '', 1, 1, 0, NOW(), NOW(), 0,
          NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
`);

test.beforeEach(async ({ page }) => {
  cleanup();
  seedUser(APPLICANT, 2, 0);
  seedUser(EMPLOYER, 1, 1);

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

test('the sidebar reaches the list, and the list shows every account type', async ({ page }) => {
  await page.goto('sadmin/dashboard');
  await page.click('a[href$="/sadmin/manageemail"]');

  await expect(page).toHaveURL(/\/sadmin\/manageemail$/);
  await expect(page.locator('.content-header h1')).toContainText('Manage Email');

  // All three account types in one list - the feature is about recipients.
  const admins = Number(scalar('SELECT COUNT(*) FROM users WHERE u_usertype = 0;'));
  const emps = Number(scalar('SELECT COUNT(*) FROM users WHERE u_usertype = 1;'));
  const apps = Number(scalar('SELECT COUNT(*) FROM users WHERE u_usertype = 2;'));
  await expect(page.locator('#example1_info')).toContainText(`of ${admins + emps + apps} entries`);

  // A fresh account reads "All emails".
  await filterTable(page, APPLICANT);
  const row = page.locator('#example1 tbody tr', { hasText: APPLICANT });
  await expect(row.locator('.badge')).toHaveText(/all emails/i);
  await expect(row).toContainText('Applicant');
  await expect(row.locator('a', { hasText: 'Email Permissions' })).toHaveCount(1);

  await expectNoServerError(page);
});

test('an applicant is offered only the e-mails an applicant can be sent', async ({ page }) => {
  const uid = scalar(`SELECT u_id FROM users WHERE u_email = '${APPLICANT}';`);

  await page.goto(`sadmin/manageemail/permissions/${uid}`);
  await expectNoServerError(page);

  const grid = page.locator('#cbg_email_allowed');

  for (const label of [...BOTH, ...APPLICANT_ONLY]) {
    await expect(grid, `${label} is on offer`).toContainText(label);
  }

  // The two an applicant can never receive: nobody posts a shift for them, and
  // the employer's half of a booking is addressed to the pharmacy.
  for (const label of EMPLOYER_ONLY) {
    await expect(grid, `${label} is not on offer`).not.toContainText(label);
  }

  await expect(grid.locator('input[type=checkbox]')).toHaveCount(4);
});

test('an employer is offered only the e-mails an employer can be sent', async ({ page }) => {
  const uid = scalar(`SELECT u_id FROM users WHERE u_email = '${EMPLOYER}';`);

  await page.goto(`sadmin/manageemail/permissions/${uid}`);
  await expectNoServerError(page);

  const grid = page.locator('#cbg_email_allowed');

  for (const label of [...BOTH, ...EMPLOYER_ONLY]) {
    await expect(grid, `${label} is on offer`).toContainText(label);
  }

  // An employer is not the one working the shift, so neither of these reaches
  // them - the reminder least of all.
  for (const label of APPLICANT_ONLY) {
    await expect(grid, `${label} is not on offer`).not.toContainText(label);
  }

  await expect(grid.locator('input[type=checkbox]')).toHaveCount(4);
});

test('an administrator is offered nothing, and told why', async ({ page }) => {
  const uid = scalar('SELECT u_id FROM users WHERE u_usertype = 0 LIMIT 1;');
  test.skip(uid === '', 'no administrator account to open');

  await page.goto(`sadmin/manageemail/permissions/${uid}`);
  await expectNoServerError(page);

  // Every one of these messages is about registering, being approved, posting a
  // shift or being booked on one. An administrator is none of those.
  await expect(page.locator('#cbg_email_allowed')).toHaveCount(0);
  await expect(page.locator('.alert-info')).toContainText('not a recipient');
  await expect(page.locator('button[name="savedata"]'), 'nothing to save').toHaveCount(0);

  // And the list says the same rather than claiming they get everything.
  // Searched on the User Type column: the administrator account carries no
  // e-mail address and no name, so there is nothing else on the row to find it
  // by - and searching for a blank matches every row on the site.
  await page.goto('sadmin/manageemail');
  await filterTable(page, 'Administrator');

  const rows = page.locator('#example1 tbody tr', { hasText: 'Administrator' });

  // Proving the rows are there is what stops the assertion below passing
  // against nothing at all.
  await expect(rows.first(), 'the administrator row is in the filtered list').toBeVisible();
  await expect(rows.locator('.badge'), 'no "all emails" claim').toHaveCount(0);
});

test('unticking types stores the inverse and reads back the same', async ({ page }) => {
  const uid = scalar(`SELECT u_id FROM users WHERE u_email = '${APPLICANT}';`);

  await page.goto(`sadmin/manageemail/permissions/${uid}`);

  // Everything on offer is ticked for a fresh account.
  const boxes = page.locator('#cbg_email_allowed input[type=checkbox]');
  const total = await boxes.count();
  await expect(page.locator('#cbg_email_allowed input:checked')).toHaveCount(total);

  // reset-password is not on offer: it is always sent.
  await expect(page.locator('#cbg_email_allowed')).not.toContainText(/reset/i);

  // Untick two, save.
  const vals = await boxes.evaluateAll((els) => els.map((e) => e.value));
  await page.click(`#cbg_email_allowed label[for="cbg_email_allowed_${vals[1]}"]`);
  await page.click(`#cbg_email_allowed label[for="cbg_email_allowed_${vals[3]}"]`);
  await page.click('button[name="savedata"]');
  await settle(page);

  // The stored value is the blocked pair - the inverse of what was ticked.
  const stored = String(scalar(`SELECT u_email_blocked FROM users WHERE u_id = ${uid};`))
    .split(',').sort().join(',');
  expect(stored).toBe([vals[1], vals[3]].sort().join(','));

  // Reopening shows exactly those two unticked.
  await page.goto(`sadmin/manageemail/permissions/${uid}`);
  const unticked = await page.locator('#cbg_email_allowed input:not(:checked)')
    .evaluateAll((els) => els.map((e) => e.value).sort());
  expect(unticked).toEqual([vals[1], vals[3]].sort());

  // And the list badge counts them - out of the four this account is offered,
  // not the six in the config.
  await page.goto('sadmin/manageemail');
  await filterTable(page, APPLICANT);
  await expect(page.locator('#example1 tbody tr', { hasText: APPLICANT })
    .locator('.badge')).toHaveText(new RegExp(`2 of ${total} blocked`, 'i'));

  await expectNoServerError(page);
});

test('a block on an e-mail this account is not offered is left alone', async ({ page }) => {
  const uid = scalar(`SELECT u_id FROM users WHERE u_email = '${APPLICANT}';`);

  // Code 3 is shift-posted - employer-only, so this applicant's page never
  // shows it. It could have been set before the list was split by side.
  query(`UPDATE users SET u_email_blocked = '3' WHERE u_id = ${uid};`);

  await page.goto(`sadmin/manageemail/permissions/${uid}`);
  await expect(page.locator('#cbg_email_allowed input:not(:checked)'),
    'nothing on this page is unticked by it').toHaveCount(0);

  await page.click('button[name="savedata"]');
  await settle(page);

  // A form changes what it showed, and nothing else.
  expect(String(scalar(`SELECT u_email_blocked FROM users WHERE u_id = ${uid};`))).toBe('3');
  await expectNoServerError(page);
});

test('unticking everything blocks everything, and reticking restores it', async ({ page }) => {
  const uid = scalar(`SELECT u_id FROM users WHERE u_email = '${APPLICANT}';`);

  await page.goto(`sadmin/manageemail/permissions/${uid}`);
  const vals = await page.locator('#cbg_email_allowed input[type=checkbox]')
    .evaluateAll((els) => els.map((e) => e.value));

  // An all-clear form posts no boxes at all; that must read as "block all",
  // not as "nothing posted, change nothing".
  for (const v of vals) {
    await page.click(`#cbg_email_allowed label[for="cbg_email_allowed_${v}"]`);
  }
  await page.click('button[name="savedata"]');
  await settle(page);

  const blocked = String(scalar(`SELECT u_email_blocked FROM users WHERE u_id = ${uid};`));
  expect(blocked.split(',').filter(Boolean).length).toBe(vals.length);

  await page.goto('sadmin/manageemail');
  await filterTable(page, APPLICANT);
  await expect(page.locator('#example1 tbody tr', { hasText: APPLICANT })
    .locator('.badge')).toHaveText(/all blocked/i);

  // Tick everything back on.
  await page.goto(`sadmin/manageemail/permissions/${uid}`);
  for (const v of vals) {
    await page.click(`#cbg_email_allowed label[for="cbg_email_allowed_${v}"]`);
  }
  await page.click('button[name="savedata"]');
  await settle(page);

  expect(String(scalar(`SELECT u_email_blocked FROM users WHERE u_id = ${uid};`))).toBe('');
  await expectNoServerError(page);
});

test('the reminder cron honours the opt-out and does not retry nightly', async ({ page }) => {
  // A booked shift tomorrow for an applicant who has opted out of reminders.
  query("DELETE FROM stu_saved_applied_jobs WHERE sj_applied_desc = 'E2E-MAILPERM';");
  query("DELETE FROM post_job WHERE p_job_title = 'E2E-MAILPERM-1';");

  const uid = scalar(`SELECT u_id FROM users WHERE u_email = '${APPLICANT}';`);
  const emp = scalar('SELECT u_id FROM users WHERE u_usertype = 1 AND u_status = 1 LIMIT 1;');
  const reminderCode = '6'; // shift-reminder in AppSettings::$emailTypes

  // "Tomorrow" has to be PHP's, not MySQL's. The app runs on
  // Config\App::$appTimezone (America/Toronto) and picks the target date with
  // date('Y-m-d', strtotime('+1 day')); the database server here is hours
  // ahead on system time, so CURDATE() + 1 can be a different day entirely and
  // the cron would never see this row.
  const tomorrow = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Toronto' })
    .format(new Date(Date.now() + 86400000));
  const [yyyy, mm, dd] = tomorrow.split('-');

  query(`UPDATE users SET u_email_blocked = '${reminderCode}' WHERE u_id = ${uid};`);
  query(`
    INSERT INTO post_job (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city,
                          p_shift_for, p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start,
                          p_shift_time, p_skills, p_services, p_additional_details, p_jobinfo,
                          p_featured, p_status, p_approved, created, modified)
    VALUES (${emp}, 0, 'E2E Mailperm Co', 'E2E-MAILPERM-1', 0, 1, 1, 1, 30, 30,
            '${dd}-${mm}-${yyyy}', '${tomorrow}', '09:00 - 17:00', '', '', '', 'seed',
            0, 1, 1, NOW(), NOW());
  `);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-MAILPERM-1';");
  query(`
    INSERT INTO stu_saved_applied_jobs (u_id, agency_id, p_id, sj_is_approved, sj_applied_desc,
                                        sj_admin_comment, created, modified)
    VALUES (${uid}, ${emp}, ${shift}, 1, 'E2E-MAILPERM', '', NOW(), NOW());
  `);

  // The cron endpoint the host calls when there is no shell. It always
  // targets tomorrow, which is when the seeded shift is.
  await page.goto('cron/remind_shifts').catch(() => {});

  // Withheld - and stamped, so tomorrow night it is not asked again.
  expect(scalar("SELECT sj_reminder_sent_at IS NOT NULL FROM stu_saved_applied_jobs WHERE sj_applied_desc = 'E2E-MAILPERM';"))
    .toBe('1');

  query("DELETE FROM stu_saved_applied_jobs WHERE sj_applied_desc = 'E2E-MAILPERM';");
  query("DELETE FROM post_job WHERE p_job_title = 'E2E-MAILPERM-1';");
});
