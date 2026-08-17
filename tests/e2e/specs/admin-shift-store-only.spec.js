// @ts-check
/**
 * The admin shift form asks for a store, and nothing else about who the shift
 * is for.
 *
 * It used to ask three questions in a row: a User Type to narrow by, then the
 * employer, then that employer's store - fetched over ajax once the employer
 * was known. But a store belongs to exactly one employer, so the last question
 * already contained the answer to the other two, and the only thing the first
 * two could add was a disagreement. Both are gone; `post_job.u_id` is read off
 * `store.u_id` when the shift is saved.
 *
 * The same reasoning took the pair off the store form, where a location is
 * filed under the owner that holds it - see admin-stores.spec.js.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');
const { pickShiftStore } = require('../helpers/stores');

const ids = {};

const cleanup = () => {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-STOREONLY-%';");
  query("DELETE FROM store WHERE s_name LIKE 'E2E Storeonly%';");
  query("DELETE FROM users WHERE u_userid LIKE 'storeonly%@e2e.test';");
};

const employer = (login, company, role) => query(`
  INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname,
                     u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo,
                     u_provice, u_city, u_address1, u_pincode, u_phone, u_email, u_terms, u_status,
                     u_collartype, created, modified, u_login_attempt, u_login_attempt_dt,
                     u_ipaddress, reset_token, token_expiry)
  VALUES (1, 0, ${role}, 0, '${login}', 'E2E', 'Storeonly', MD5('x'), '${company}', 0, '', '', '',
          ${ids.province}, ${ids.city}, '', '', '0000000000', '${login}', 1, 1, 0, NOW(), NOW(), 0,
          NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
`);

// The store's own defaults tick Software and Details on the shift form, and
// the form will not submit without them - so a fixture store has to carry
// them exactly as a real one does.
const store = (ownerId, name) => {
  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${ownerId}, '${name}', 'S-1', ${ids.province}, ${ids.city}, 'x', 'x',
                 '0000000000', '${ids.skill}', '${ids.service}', '', 1, NOW(), NOW());`);

  return scalar(`SELECT s_id FROM store WHERE s_name = '${name}';`);
};

test.beforeEach(async ({ page }) => {
  cleanup();

  ids.city = scalar('SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1;');
  ids.province = scalar(`SELECT c_province FROM city WHERE c_id = ${ids.city};`);
  ids.shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_id LIMIT 1;');
  ids.skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 ORDER BY ss_id LIMIT 1;');
  ids.service = scalar('SELECT st_id FROM store_service WHERE st_status = 1 ORDER BY st_id LIMIT 1;');

  employer('storeonlyone@e2e.test', 'E2E Storeonly Alpha', 1);
  employer('storeonlytwo@e2e.test', 'E2E Storeonly Beta', 1);
  ids.alpha = scalar("SELECT u_id FROM users WHERE u_comp_name = 'E2E Storeonly Alpha';");
  ids.beta = scalar("SELECT u_id FROM users WHERE u_comp_name = 'E2E Storeonly Beta';");

  // The same store name under two different chains: only the grouping tells
  // them apart, which is why the picker has to carry the owner.
  ids.alphaStore = store(ids.alpha, 'E2E Storeonly Shared Name');
  ids.betaStore = store(ids.beta, 'E2E Storeonly Beta Branch');

  // Somebody runs the Beta branch. Alpha's is deliberately left unmanaged, so
  // the note has both cases to show.
  employer('storeonlymgr@e2e.test', '', 2);
  query(`UPDATE users SET u_fname = 'Priya', u_lname = 'Raman', u_parent_id = ${ids.beta},
                          u_store_id = ${ids.betaStore}
          WHERE u_userid = 'storeonlymgr@e2e.test';`);

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

test('the form asks for a store and never for an employer', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');
  await expectNoServerError(page);

  // The form opens on the store question: the group that narrows the list,
  // then the store itself.
  const ids_ = await page.locator('.card-body select').evaluateAll((els) =>
    els.map((e) => e.id).filter(Boolean));
  expect(ids_.slice(0, 2)).toEqual(['p_store_group', 'p_store_id']);

  // Neither of the two it replaced is anywhere on the page. The group dropdown
  // is not one of them coming back: it is unnamed, so nothing is posted for it
  // and the employer is still read off the store.
  await expect(page.locator('#u_emp_kind')).toHaveCount(0);
  await expect(page.locator('#u_id')).toHaveCount(0);
  await expect(page.locator('[name="u_id"]')).toHaveCount(0);
  await expect(page.locator('#p_store_group')).not.toHaveAttribute('name', /./);

  // Both chains are offered as groups.
  const groups = await page.locator('#p_store_group option').evaluateAll((els) =>
    els.map((e) => (e.textContent || '').trim()));
  expect(groups).toContain('E2E Storeonly Alpha');
  expect(groups).toContain('E2E Storeonly Beta');

  // And the store list starts empty, holding only its placeholder, until one
  // of them is picked.
  const stores = await page.locator('#p_store_id option').evaluateAll((els) =>
    els.map((e) => e.value).filter(Boolean));
  expect(stores).toEqual([]);
});

test('the shift is saved for whoever owns the store that was chosen', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  await pickShiftStore(page, ids.betaStore);

  // The store's defaults tick the two required groups; wait for them rather
  // than ticking by hand, or the submit races the fill.
  await expect.poll(() => page.locator('#cbg_p_skills input:checked').count()).toBeGreaterThan(0);

  await page.selectOption('select[name="p_shift_for"]', String(ids.shiftFor));
  await page.fill('input[name="p_hourly_rate"]', '40');
  await page.fill('input[name="p_ac_hourly_rate"]', '45');
  await page.evaluate(() => {
    window.jQuery('input[name="p_dates"]').datepicker('setDate', new Date(2027, 8, 1));
  });
  await expect(page.locator('input[name="p_dates"]')).toHaveValue('01-09-2027');

  await settle(page);
  await Promise.all([page.waitForLoadState('load'), page.click('input[name="savedata"]')]);
  await settle(page);
  await expectNoServerError(page);

  const shift = scalar(`SELECT p_id FROM post_job WHERE p_store_id = ${ids.betaStore};`);
  expect(shift, 'the shift was saved').not.toBe('');

  // Never posted by the form - read off the store.
  expect(scalar(`SELECT u_id FROM post_job WHERE p_id = ${shift};`),
    "the store's owner is the shift's employer").toBe(String(ids.beta));

  // And the location follows the store, as it always did.
  expect(scalar(`SELECT p_province FROM post_job WHERE p_id = ${shift};`)).toBe(String(ids.province));
  expect(scalar(`SELECT p_city FROM post_job WHERE p_id = ${shift};`)).toBe(String(ids.city));

  query(`DELETE FROM post_job WHERE p_id = ${shift};`);
});

test('the edit screen opens on the shift own store', async ({ page }) => {
  query(`INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
       p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES (${ids.alpha}, ${ids.alphaStore}, 'E2E Storeonly Co', 'E2E-STOREONLY-1', 0,
       ${ids.province}, ${ids.city}, ${ids.shiftFor}, 30, 30, '01-09-2027', '2027-09-01',
       '09:00 - 17:00', '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());`);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-STOREONLY-1';");

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await expectNoServerError(page);

  await expect(page.locator('#p_store_id')).toHaveValue(String(ids.alphaStore));

  // Both dropdowns arrive already on the shift's own store, rendered by the
  // server - nothing runs on load to put them there, which is what leaves the
  // shift's saved tick-boxes alone.
  await expect(page.locator('#p_store_group')).toHaveValue(String(ids.alpha));
  await expect(page.locator('#u_id')).toHaveCount(0);
});

test('moving a shift to another chain store moves the shift to that chain', async ({ page }) => {
  query(`INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
       p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES (${ids.alpha}, ${ids.alphaStore}, 'E2E Storeonly Co', 'E2E-STOREONLY-2', 0,
       ${ids.province}, ${ids.city}, ${ids.shiftFor}, 30, 30, '01-09-2027', '2027-09-01',
       '09:00 - 17:00', '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());`);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-STOREONLY-2';");

  await page.goto(`sadmin/postjobs/edit/${shift}`);
  await pickShiftStore(page, ids.betaStore);

  await settle(page);
  await Promise.all([page.waitForLoadState('load'), page.click('input[name="savedata"]')]);
  await settle(page);
  await expectNoServerError(page);

  // A branch changing hands is a real thing to want, and naming the store is
  // the only way the form can say it now.
  expect(scalar(`SELECT p_store_id FROM post_job WHERE p_id = ${shift};`)).toBe(String(ids.betaStore));
  expect(scalar(`SELECT u_id FROM post_job WHERE p_id = ${shift};`)).toBe(String(ids.beta));
});

test('the note names the manager, or the owner when there is none', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  // Before a store is chosen there is nobody to name.
  await expect(page.locator('#store_owner_note')).toHaveText(/employer that owns this store/i);

  await pickShiftStore(page, ids.betaStore);

  // The manager alone: the store is in the second dropdown and its owner is
  // showing in the first, so repeating either would say nothing new.
  const note = page.locator('#store_owner_note');
  await expect(note).toContainText('Managed by (Priya Raman)');
  expect(await note.locator('strong').allTextContents()).toEqual(['Priya Raman']);

  // Neither the store nor its owner is repeated: one is the option showing in
  // the store dropdown, the other the one showing in the group dropdown.
  await expect(note).not.toContainText('E2E Storeonly Beta Branch');
  await expect(note).not.toContainText('E2E Storeonly Beta,');

  // Most stores have no manager account - the owner runs it themselves - so
  // those name the owner instead of leaving the line saying nobody.
  await pickShiftStore(page, ids.alphaStore);
  await expect(note).toContainText('Owned by (E2E Storeonly Alpha)');
  expect(await note.locator('strong').allTextContents()).toEqual(['E2E Storeonly Alpha']);
});

test('the note is on the edit screen from the first paint', async ({ page }) => {
  query(`INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
       p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES (${ids.beta}, ${ids.betaStore}, 'E2E Storeonly Co', 'E2E-STOREONLY-3', 0,
       ${ids.province}, ${ids.city}, ${ids.shiftFor}, 30, 30, '01-09-2027', '2027-09-01',
       '09:00 - 17:00', '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());`);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-STOREONLY-3';");

  await page.goto(`sadmin/postjobs/edit/${shift}`);

  // Rendered by the server, not filled in by the change handler: opening a
  // shift and touching nothing must still name the manager.
  const note = page.locator('#store_owner_note');
  await expect(note).toContainText('Managed by (Priya Raman)');
  expect(await note.locator('strong').allTextContents()).toEqual(['Priya Raman']);
});
