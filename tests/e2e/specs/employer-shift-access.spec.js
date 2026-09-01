// @ts-check
/**
 * Who may edit or delete a shift on the employer's own side of the site.
 *
 *   - the login that posted it;
 *   - if a manager posted it, the owner they answer to;
 *   - if the owner posted it, the manager of the branch it is at;
 *   - nobody else, and never a sibling branch's manager;
 *   - the back office, which is not bound by any of this.
 *
 * edit_job(), delete_job() and applied_applicants() used to match on the shift
 * id alone, so every employer could open, take over or destroy every other
 * employer's pending shift - and a save stamped the editor's own ownership and
 * store over the row. These pin the rule from both sides: that the right people
 * still get in, and that nobody else does.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { loginAsFrontUser } = require('../helpers/front');
const { query, scalar, count } = require('../helpers/db');
const { csrfHeaders } = require('../helpers/csrf');

const PASSWORD = 'E2eTest@12345';
const PREFIX = 'e2e.access.';

const OWNER = { user: `${PREFIX}owner@example.com`, pass: PASSWORD };
const MANAGER = { user: `${PREFIX}manager@example.com`, pass: PASSWORD };
const OUTSIDER = { user: `${PREFIX}outsider@example.com`, pass: PASSWORD };

/** @type {{owner: number, manager: number, outsider: number}} */
let user;

/** @type {{branch: number, other: number, outside: number}} */
let store;

function removeFixtures() {
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
      (1, 0, ${shape.role}, ${shape.parent || 0}, ${shape.storeId || 0}, '${account.user}', 'Access', 'E2E',
       MD5('${account.pass}'), '${shape.company}', 0, 'E2E-ACCESS', '', '',
       -- A real province and a city *in that province*: both selects on the
       -- profile form are required and the city list is fetched for the chosen
       -- province, so a mismatched pair leaves it empty and the browser refuses
       -- to submit - the same trap the deploy notes describe for stores.
       (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       '1 Access Road', 'M5A 1A1', '4160000800', '${account.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${account.user}';`));
}

/**
 * @param {number} ownerId
 * @param {string} name
 * @returns {number} store.s_id
 */
function seedStore(ownerId, name) {
  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${ownerId}, '${name}', '${name.replace(/[^0-9A-Za-z]/g, '')}',
            (SELECT p_id FROM province ORDER BY p_id LIMIT 1),
            (SELECT c_id FROM city ORDER BY c_id LIMIT 1),
            '1 Access Road', 'M5A 1A1', '4160000801', 1);
  `);

  return Number(scalar(`SELECT MAX(s_id) FROM store WHERE s_name = '${name}';`));
}

/**
 * A pending, unapproved shift - the state edit and delete both require.
 *
 * @param {number} ownerId  users.u_id it is posted by
 * @param {number} storeId  store.s_id it is at
 * @param {string} title
 * @returns {number} post_job.p_id
 */
function seedShift(ownerId, storeId, title) {
  query(`
    INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
       p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES
      (${ownerId}, ${storeId}, 'E2E Access Co', '${title}', 0,
       (SELECT p_id FROM province ORDER BY p_id LIMIT 1), (SELECT c_id FROM city ORDER BY c_id LIMIT 1),
       (SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1),
       30, 30, '05-11-2027', '2027-11-05', '09:00 - 17:00',
       (SELECT ss_id FROM software_skills WHERE ss_status = 1 LIMIT 1),
       (SELECT st_id FROM store_service WHERE st_status = 1 LIMIT 1),
       'Seeded by the end-to-end suite.', 0, 0, 0, '2020-01-01 10:00:00', NOW());
  `);

  return Number(scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${title}';`));
}

test.beforeAll(() => {
  removeFixtures();

  user = { owner: 0, manager: 0, outsider: 0 };
  user.owner = seedEmployer(OWNER, { role: 1, company: 'E2E Access Group' });

  store = {
    branch: seedStore(user.owner, 'E2E Access Branch'),
    other: seedStore(user.owner, 'E2E Access Other'),
    outside: 0,
  };

  user.manager = seedEmployer(MANAGER, {
    role: 2,
    parent: user.owner,
    storeId: store.branch,
    company: 'E2E Access Branch',
  });

  user.outsider = seedEmployer(OUTSIDER, { role: 1, company: 'E2E Access Outsider' });
  store.outside = seedStore(user.outsider, 'E2E Access Outside');
});

test.afterAll(removeFixtures);

/** A fresh set of shifts per test, so one test's edit cannot mislead the next. */
function seedShifts() {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-ACCESS-%';");

  return {
    byManager: seedShift(user.manager, store.branch, 'E2E-ACCESS-MGR'),
    byOwnerAtBranch: seedShift(user.owner, store.branch, 'E2E-ACCESS-OWN-BRANCH'),
    byOwnerElsewhere: seedShift(user.owner, store.other, 'E2E-ACCESS-OWN-OTHER'),
    byOutsider: seedShift(user.outsider, store.outside, 'E2E-ACCESS-OUT'),
  };
}

/**
 * Open a shift for editing and say whether the form came up.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} pid
 */
async function canEdit(page, pid) {
  await page.goto(`employer/edit_job/${pid}`);
  await settle(page);

  return (await page.locator('select[name="p_store_id"]').count()) > 0;
}

test('a manager edits their own shift, and their owner can too', async ({ page }) => {
  const shift = seedShifts();

  await loginAsFrontUser(page, MANAGER);
  expect(await canEdit(page, shift.byManager), 'the manager who posted it').toBe(true);
  await page.goto('front/logout');

  await loginAsFrontUser(page, OWNER);
  expect(await canEdit(page, shift.byManager), 'the owner they answer to').toBe(true);
  await expectNoServerError(page);
});

test('a manager edits the shift their owner posted for the branch they run', async ({ page }) => {
  const shift = seedShifts();

  await loginAsFrontUser(page, MANAGER);
  expect(await canEdit(page, shift.byOwnerAtBranch), "their own branch's shift").toBe(true);

  // But not one at the group's other branch, which is not theirs to run: the
  // form is refused and they are put back on the list. (The "Invalid Shift
  // Post" message set alongside the redirect does not survive it - flashdata is
  // lost across every redirect on this side of the site - so the landing page
  // is what there is to assert.)
  expect(await canEdit(page, shift.byOwnerElsewhere), "a sibling branch's shift").toBe(false);
  await expect(page).toHaveURL(/employer\/all_jobs/);
  await expectNoServerError(page);
});

test('another employer cannot open, take over or delete a shift', async ({ page }) => {
  const shift = seedShifts();

  const before = scalar(`SELECT CONCAT_WS('|', u_id, p_store_id, created) FROM post_job WHERE p_id = ${shift.byManager};`);

  await loginAsFrontUser(page, OUTSIDER);

  expect(await canEdit(page, shift.byManager), "someone else's shift").toBe(false);
  await expect(page).toHaveURL(/employer\/all_jobs/);

  expect(await canEdit(page, shift.byOwnerAtBranch), "someone else's owner shift").toBe(false);

  // Nothing about the row moved - it used to become the caller's on save.
  expect(
    scalar(`SELECT CONCAT_WS('|', u_id, p_store_id, created) FROM post_job WHERE p_id = ${shift.byManager};`),
    'the shift is untouched',
  ).toBe(before);

  // And the delete URL, typed straight in, does nothing either.
  await page.goto(`employer/delete_job/${shift.byManager}`);
  await settle(page);

  expect(count('post_job', `p_id = ${shift.byManager}`), 'the shift still exists').toBe(1);
  await expectNoServerError(page);
});

test('the owner can delete their manager\'s shift, and the outsider still cannot', async ({ page }) => {
  const shift = seedShifts();

  await loginAsFrontUser(page, OUTSIDER);
  await page.goto(`employer/delete_job/${shift.byManager}`);
  await settle(page);
  expect(count('post_job', `p_id = ${shift.byManager}`), 'not the outsider').toBe(1);
  await page.goto('front/logout');

  await loginAsFrontUser(page, OWNER);
  await page.goto(`employer/delete_job/${shift.byManager}`);
  await settle(page);
  expect(count('post_job', `p_id = ${shift.byManager}`), 'the owner may').toBe(0);
  await expectNoServerError(page);
});

test('saving somebody else\'s shift keeps its owner and its posted date', async ({ page }) => {
  const shift = seedShifts();

  await loginAsFrontUser(page, OWNER);
  await page.goto(`employer/edit_job/${shift.byManager}`);
  await settle(page);

  await page.fill('input[name="p_hourly_rate"]', '44');

  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savepostjob"]'),
  ]);
  await settle(page);

  const row = scalar(`
    SELECT CONCAT_WS('|', u_id, p_store_id, p_hourly_rate, created)
      FROM post_job WHERE p_id = ${shift.byManager};
  `).split('|');

  expect(row[0], 'the shift still belongs to the manager who raised it').toBe(String(user.manager));
  expect(row[1], 'and is still at their branch').toBe(String(store.branch));
  expect(row[2], 'the edit took').toBe('44');
  expect(row[3], 'an edit is not a posting, so the posted date stands').toBe('2020-01-01 10:00:00');
  await expectNoServerError(page);
});

test('the shift list holds what each login may manage, and nothing else', async ({ page }) => {
  const shift = seedShifts();

  await loginAsFrontUser(page, OWNER);
  await page.goto('employer/all_jobs');
  await settle(page);

  const ownerSees = page.locator('#joblist');
  await expect(ownerSees, "the owner's own shift").toContainText('E2E-ACCESS-OWN-BRANCH');
  await expect(ownerSees, "their manager's shift").toContainText('E2E-ACCESS-MGR');
  await expect(ownerSees, "not another employer's").not.toContainText('E2E-ACCESS-OUT');
  await page.goto('front/logout');

  await loginAsFrontUser(page, MANAGER);
  await page.goto('employer/all_jobs');
  await settle(page);

  const managerSees = page.locator('#joblist');
  await expect(managerSees, 'their own shift').toContainText('E2E-ACCESS-MGR');
  await expect(managerSees, "the owner's shift at their branch").toContainText('E2E-ACCESS-OWN-BRANCH');
  await expect(managerSees, "not the group's other branch").not.toContainText('E2E-ACCESS-OWN-OTHER');
  await expect(managerSees, "not another employer's").not.toContainText('E2E-ACCESS-OUT');

  expect(shift.byOwnerElsewhere).toBeGreaterThan(0);
  await expectNoServerError(page);
});

test('a hand-made post cannot set columns the shift form does not show', async ({ page }) => {
  const shift = seedShifts();
  const before = scalar(`
    SELECT CONCAT_WS('|', u_id, created, p_approved, p_status)
      FROM post_job WHERE p_id = ${shift.byManager};
  `);

  await loginAsFrontUser(page, OWNER);
  await page.goto(`employer/edit_job/${shift.byManager}`);
  await settle(page);

  // A field the form does show, so a submit that never happened cannot pass
  // this test by leaving the row alone.
  await page.fill('input[name="p_hourly_rate"]', '52');

  // The form has no field for any of these. A crafted request does.
  await page.evaluate((owner) => {
    const form = document.querySelector('input[name="savepostjob"]')?.closest('form');
    if (!form) throw new Error('no shift form on the page');

    for (const [name, value] of [
      ['u_id', String(owner)],
      ['created', '2030-01-01 00:00:00'],
      ['p_approved', '1'],
      ['p_status', '1'],
      ['p_id', '999999'],
    ]) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      form.appendChild(input);
    }
  }, user.owner);

  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savepostjob"]'),
  ]);
  await settle(page);

  expect(
    scalar(`SELECT CONCAT_WS('|', u_id, created, p_approved, p_status) FROM post_job WHERE p_id = ${shift.byManager};`),
    'ownership, posted date and both status flags are the form\'s to set, not the browser\'s',
  ).toBe(before);

  expect(
    scalar(`SELECT p_hourly_rate FROM post_job WHERE p_id = ${shift.byManager};`),
    'and the save really did go through',
  ).toBe('52');
  await expectNoServerError(page);
});

test('an employer cannot make themselves the manager of somebody else\'s branch', async ({ page }) => {
  seedShifts();

  await loginAsFrontUser(page, OUTSIDER);
  await page.goto('employer/edit_profile');
  await settle(page);

  // A field the form does show, changed at the same time: without it a submit
  // that never happened would pass this test by leaving everything alone.
  await page.fill('input[name="u_fname"]', 'Renamed');

  // The profile form shows a name, phone and address - not what kind of account
  // this is, nor which branch it runs. Posting them anyway used to write them,
  // which handed the poster every shift at that branch.
  await page.evaluate((branch) => {
    const form = document.querySelector('[name="updateprofile"]')?.closest('form');
    if (!form) throw new Error('no profile form on the page');

    for (const [name, value] of [['u_emp_role', '2'], ['u_store_id', String(branch)], ['u_parent_id', '1']]) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      form.appendChild(input);
    }
  }, store.branch);

  await Promise.all([
    page.waitForLoadState('load'),
    page.locator('[name="updateprofile"]').click(),
  ]);
  await settle(page);

  const row = scalar(`
    SELECT CONCAT_WS('|', u_emp_role, IFNULL(u_store_id, 0), IFNULL(u_parent_id, 0), u_fname)
      FROM users WHERE u_id = ${user.outsider};
  `);

  expect(row, 'the save happened, and only the field the form shows moved').toBe('1|0|0|Renamed');
  await expectNoServerError(page);
});

test('the shortlist endpoint refuses a shift that is not the caller\'s', async ({ page }) => {
  const shift = seedShifts();
  const applicant = scalar('SELECT u_id FROM users WHERE u_usertype = 2 AND u_status = 1 LIMIT 1;');
  test.skip(applicant === '', 'no active applicant to invite');

  await loginAsFrontUser(page, OUTSIDER);

  const before = count('stu_saved_applied_jobs', `p_id = ${shift.byManager}`);

  // Same session, straight to the endpoint - which is all the page does. The
  // token goes with it for the same reason: the page sends one, and this is
  // asking who may call the endpoint, not whether CSRF works.
  const res = await page.request.post('employer/ajax_shortlist', {
    form: { uid: applicant, jobid: String(shift.byManager) },
    headers: await csrfHeaders(page),
  });

  expect((await res.text()).trim(), 'refused').toBe('0');
  expect(
    count('stu_saved_applied_jobs', `p_id = ${shift.byManager}`),
    'no invitation was written against a shift they do not manage',
  ).toBe(before);
});

test('the applicant list answers only for a shift the login may see', async ({ page }) => {
  const shift = seedShifts();

  await loginAsFrontUser(page, OUTSIDER);
  await page.goto(`employer/applied_applicants/${shift.byManager}`);
  await settle(page);

  await expect(page, 'turned away rather than shown the applicants').toHaveURL(/employer\/all_jobs/);
  await page.goto('front/logout');

  // The people it is for still get in.
  await loginAsFrontUser(page, OWNER);
  await page.goto(`employer/applied_applicants/${shift.byManager}`);
  await settle(page);

  await expect(page, "the owner reaches their manager's applicants").not.toHaveURL(/employer\/all_jobs/);
  await expectNoServerError(page);
});
