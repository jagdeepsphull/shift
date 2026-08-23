// @ts-check
/**
 * Shift defaults set by the employer on their own store form.
 *
 * The back office could already say what a store normally offers, and the admin
 * shift form copied it onto a new shift. The employer could not: they were the
 * ones who knew, and the one screen where the answer belongs - their own Add
 * Store - did not ask. So every shift was ticked out by hand each time.
 *
 * These check the whole round trip on the employer's side:
 *
 *   1. the store form asks for the three lists, and saves what was ticked;
 *   2. choosing that store on their shift form arrives already ticked;
 *   3. their manager - who owns no store row - gets the same defaults, which is
 *      the case the request called out by name;
 *   4. the copy stays one-way: correcting a shift never writes back to the
 *      store.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const {
  loginAsAgency,
  loginAsFrontUser,
  seedShiftFixture,
  removeShiftFixture,
} = require('../helpers/front');
const { query, scalar } = require('../helpers/db');
const { csrfHeaders } = require('../helpers/csrf');

const ids = {};
const STORE_NAME = 'E2E Esdef Branch';

const cleanup = () => {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-ESDEF-%';");
  query(`DELETE FROM store WHERE s_name = '${STORE_NAME}';`);
  query("DELETE FROM users WHERE u_userid = 'e2e.esdef.manager@example.com';");
};

/** The ids ticked in one checkbox group, as strings. */
const ticked = (page, name) =>
  page.locator(`#cbg_${name} input:checked`).evaluateAll((els) =>
    els.map((e) => /** @type {HTMLInputElement} */ (e).value).sort());

/**
 * Turn one box in a group on or off.
 *
 * The box itself is a Bootstrap custom control - the input is hidden under its
 * own label, so a click on the input is intercepted. The label is what a person
 * clicks, and what the rest of the suite clicks.
 */
const toggle = (page, name, id) =>
  page.click(`#cbg_${name} label[for="cbg_${name}_${id}"]`);

test.beforeEach(async () => {
  cleanup();

  // The approved employer login the front-end specs share.
  ({ agencyId: ids.owner } = seedShiftFixture());
  expect(ids.owner, 'the seeded employer login must exist').toBeTruthy();

  ids.city = scalar('SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1;');
  ids.province = scalar(`SELECT c_province FROM city WHERE c_id = ${ids.city};`);

  ids.skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 ORDER BY ss_id LIMIT 1;');
  ids.service = scalar('SELECT st_id FROM store_service WHERE st_status = 1 ORDER BY st_id LIMIT 1;');
  ids.detail = scalar('SELECT ad_id FROM additional_details WHERE ad_status = 1 ORDER BY ad_id LIMIT 1;');
});

test.afterAll(() => {
  cleanup();
  removeShiftFixture();
});

/** The store the employer would have created, with its defaults already set. */
const seedStore = () => {
  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${ids.owner}, '${STORE_NAME}', 'ES-1', ${ids.province}, ${ids.city},
                 'x', 'M5A 1A1', '4160000101', '${ids.skill}', '${ids.service}',
                 '${ids.detail}', 1, NOW(), NOW());`);

  return scalar(`SELECT s_id FROM store WHERE s_name = '${STORE_NAME}';`);
};

test('the employer store form asks for the three lists and saves them', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/add_store');
  await expectNoServerError(page);

  // The same three groups the back office's store form carries, so the two
  // screens cannot describe a store differently.
  await expect(page.locator('#cbg_s_skills')).toHaveCount(1);
  await expect(page.locator('#cbg_s_services')).toHaveCount(1);
  await expect(page.locator('#cbg_s_additional_details')).toHaveCount(1);

  // None of them may be required here: a store that offers nothing in
  // particular is a real store, it just starts its shifts blank.
  await expect(page.locator('#cbg_s_skills')).toHaveAttribute('data-required', '0');

  await page.fill('input[name="s_name"]', STORE_NAME);
  await page.fill('input[name="s_number"]', 'ES-1');
  await page.fill('textarea[name="s_address"]', 'x');
  await page.selectOption('select[name="s_province"]', String(ids.province));
  await expect(page.locator('#city option')).not.toHaveCount(1);
  await page.selectOption('select[name="s_city"]', String(ids.city));
  await page.fill('input[name="s_pincode"]', 'M5A 1A1');

  await toggle(page, 's_skills', ids.skill);
  await toggle(page, 's_services', ids.service);
  await toggle(page, 's_additional_details', ids.detail);

  await settle(page);
  await Promise.all([page.waitForLoadState('load'), page.click('input[name="savestore"]')]);
  await settle(page);
  await expectNoServerError(page);

  const saved = scalar(`SELECT s_id FROM store WHERE s_name = '${STORE_NAME}';`);
  expect(saved, 'the store was created').toBeTruthy();

  expect(scalar(`SELECT s_skills FROM store WHERE s_id = ${saved};`)).toBe(String(ids.skill));
  expect(scalar(`SELECT s_services FROM store WHERE s_id = ${saved};`)).toBe(String(ids.service));
  expect(scalar(`SELECT s_additional_details FROM store WHERE s_id = ${saved};`)).toBe(String(ids.detail));

  // And reopening it shows what was ticked, rather than a blank set that would
  // clear the columns on the next save.
  await page.goto(`employer/edit_store/${saved}`);
  await expectNoServerError(page);

  expect(await ticked(page, 's_skills')).toEqual([String(ids.skill)]);
  expect(await ticked(page, 's_services')).toEqual([String(ids.service)]);
  expect(await ticked(page, 's_additional_details')).toEqual([String(ids.detail)]);
});

test('choosing the store on the employer shift form arrives already ticked', async ({ page }) => {
  const store = seedStore();

  await loginAsAgency(page);
  await page.goto('employer/post_job');
  await expectNoServerError(page);

  // Nothing is ticked before a store is chosen.
  expect(await ticked(page, 'p_services')).toEqual([]);

  await page.selectOption('#p_store_id', String(store));

  await expect.poll(() => ticked(page, 'p_skills')).toEqual([String(ids.skill)]);
  expect(await ticked(page, 'p_services')).toEqual([String(ids.service)]);
  expect(await ticked(page, 'p_additional_details')).toEqual([String(ids.detail)]);
});

test('the manager of that store gets the same defaults', async ({ page }) => {
  const store = seedStore();
  const manager = { user: 'e2e.esdef.manager@example.com', pass: 'E2eTest@12345' };

  // A manager owns no store row - theirs is the group's branch they were
  // assigned - so this is the case that proves the defaults follow the store
  // and not whoever happens to own it.
  query(`INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid, u_fname, u_lname,
       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, 2, ${ids.owner}, ${store}, '${manager.user}', 'Esdef', 'Manager',
       MD5('${manager.pass}'), '${STORE_NAME}', 0, 'ES-1', '', '', 0, 0,
       'x', 'M5A 1A1', '4160000102', '${manager.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');`);

  await loginAsFrontUser(page, manager);
  await page.goto('employer/post_job');
  await expectNoServerError(page);

  await page.selectOption('#p_store_id', String(store));

  await expect.poll(() => ticked(page, 'p_skills')).toEqual([String(ids.skill)]);
  expect(await ticked(page, 'p_services')).toEqual([String(ids.service)]);
});

test('a store belonging to somebody else tells the asker nothing', async ({ page }) => {
  const store = seedStore();

  // The endpoint answers only for a store the login may post against. Asked
  // for one it may not, it returns the empty answer rather than that branch's
  // list - the store here belongs to the employer, and the applicant is not
  // them.
  query(`UPDATE store SET u_id = 0 WHERE s_id = ${store};`);

  await loginAsAgency(page);
  await page.goto('employer/post_job');

  // Asked over the logged-in session, the way the form itself asks - token
  // included, because the form sends one.
  const res = await page.request.post('employer/ajax_getstoredefaults', {
    form: { s_id: String(store) },
    headers: await csrfHeaders(page),
  });
  const answer = await res.json();

  expect(answer.s_id, 'a store this login may not use is not answered for').toBe(0);
  expect(answer.p_skills).toEqual([]);
});

test('correcting a shift never writes back to the store', async ({ page }) => {
  const store = seedStore();

  await loginAsAgency(page);
  await page.goto('employer/post_job');

  await page.selectOption('#p_store_id', String(store));
  await expect.poll(() => ticked(page, 'p_skills')).toEqual([String(ids.skill)]);

  // Clear what the store suggested, exactly as somebody correcting one shift
  // would. Software is a required group on this form, so the shift is not
  // saved here - the store is what must not have moved.
  await toggle(page, 'p_additional_details', ids.detail);
  await expect.poll(() => ticked(page, 'p_additional_details')).toEqual([]);

  expect(scalar(`SELECT s_additional_details FROM store WHERE s_id = ${store};`),
    'the store keeps its own list').toBe(String(ids.detail));
});
