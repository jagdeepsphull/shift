// @ts-check
/**
 * A store's shift defaults: what it normally offers, copied onto a new shift
 * when that store is chosen.
 *
 * The copy is one-way and one-time. These check the two directions that would
 * quietly corrupt data if they leaked: correcting a shift must not write back
 * to the store, and reopening a saved shift must show the shift's own boxes
 * rather than the store's current ones.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

const ids = {};

const cleanup = () => {
  query("DELETE FROM post_job WHERE p_job_title LIKE 'E2E-SDEF-%' OR p_company_name LIKE 'E2E Sdef%';");
  query("DELETE FROM store WHERE s_name LIKE 'E2E Sdef%';");
  query("DELETE FROM users WHERE u_comp_name LIKE 'E2E Sdef%' OR u_userid LIKE 'sdef%@e2e.test';");
  query("DELETE FROM additional_details WHERE ad_name LIKE 'E2E Sdef%';");
};

test.beforeEach(async ({ page }) => {
  cleanup();

  query("INSERT INTO additional_details (ad_name, ad_status) VALUES ('E2E Sdef Parking', 1);");
  query("INSERT INTO additional_details (ad_name, ad_status) VALUES ('E2E Sdef Locker', 1);");
  ids.detailA = scalar("SELECT ad_id FROM additional_details WHERE ad_name = 'E2E Sdef Parking';");
  ids.detailB = scalar("SELECT ad_id FROM additional_details WHERE ad_name = 'E2E Sdef Locker';");

  ids.skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 ORDER BY ss_id LIMIT 1;');
  ids.serviceA = scalar('SELECT st_id FROM store_service WHERE st_status = 1 ORDER BY st_id LIMIT 1;');
  ids.serviceB = scalar('SELECT st_id FROM store_service WHERE st_status = 1 ORDER BY st_id DESC LIMIT 1;');

  // A chain, so two stores under one login prove that switching store replaces
  // the defaults rather than adding to them.
  query(`INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname,
                            u_lname, u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo,
                            u_photo, u_provice, u_city, u_address1, u_pincode, u_phone, u_email,
                            u_terms, u_status, u_collartype, created, modified, u_login_attempt,
                            u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
         VALUES (1, 0, 1, 0, 'sdef@e2e.test', 'E2E', 'Sdef', MD5('x'), 'E2E Sdef Chain', 0, '', '',
                 '', 1, 1, '', '', '0000000000', 'sdef@e2e.test', 1, 1, 0, NOW(), NOW(), 0, NOW(),
                 '127.0.0.1', '', '1970-01-01 00:00:00');`);
  ids.owner = scalar("SELECT u_id FROM users WHERE u_comp_name = 'E2E Sdef Chain';");

  // The city has to be one that belongs to the province, not just any row:
  // the store form's city list is filled by ajax from the chosen province, and
  // `s_city` is a required select - a mismatched pair leaves it empty and the
  // browser silently refuses to submit the form.
  ids.city = scalar('SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1;');
  ids.province = scalar(`SELECT c_province FROM city WHERE c_id = ${ids.city};`);

  const store = (name, skills, services, details) => query(
    `INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone,
                        s_skills, s_services, s_additional_details, s_status, created, modified)
     VALUES (${ids.owner}, '${name}', '1', ${ids.province}, ${ids.city},
             'x', 'x', '0000000000', '${skills}', '${services}', '${details}', 1, NOW(), NOW());`,
  );

  store('E2E Sdef Store One', ids.skill, ids.serviceA, ids.detailA);
  store('E2E Sdef Store Two', '', ids.serviceB, ids.detailB); // deliberately no software
  ids.storeOne = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Sdef Store One';");
  ids.storeTwo = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Sdef Store Two';");

  await loginAsAdmin(page);
});

test.afterAll(cleanup);

/** The values ticked in one group, as strings, sorted. */
const ticked = (page, name) =>
  page.locator(`#cbg_${name} input:checked`).evaluateAll((els) => els.map((e) => e.value).sort());

test('the store form carries the three lists and keeps what was ticked', async ({ page }) => {
  await page.goto(`sadmin/stores/edit/${ids.storeOne}?owner=${ids.owner}`);

  for (const group of ['s_skills', 's_services', 's_additional_details']) {
    await expect(page.locator(`#cbg_${group}`), group).toBeVisible();
    // A store may legitimately offer nothing, so none of them is required.
    await expect(page.locator(`#cbg_${group}`)).toHaveAttribute('data-required', '0');
  }

  expect(await ticked(page, 's_skills')).toEqual([String(ids.skill)]);
  expect(await ticked(page, 's_services')).toEqual([String(ids.serviceA)]);
  expect(await ticked(page, 's_additional_details')).toEqual([String(ids.detailA)]);

  await expectNoServerError(page);
});

test('choosing a store fills the shift form from that store', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');

  // Nothing is ticked before a store is chosen.
  expect(await ticked(page, 'p_services')).toEqual([]);

  // No employer is named anywhere on this form - the store carries that.
  await expect(page.locator(`#p_store_id option[value="${ids.storeOne}"]`)).toHaveCount(1);

  await page.selectOption('#p_store_id', String(ids.storeOne));
  await expect
    .poll(() => ticked(page, 'p_additional_details'))
    .toEqual([String(ids.detailA)]);

  expect(await ticked(page, 'p_skills')).toEqual([String(ids.skill)]);
  expect(await ticked(page, 'p_services')).toEqual([String(ids.serviceA)]);

  await expectNoServerError(page);
});

test('every store is on the list, under the employer that owns it', async ({ page }) => {
  // A second login with a single store, so the list has two owners in it.
  query(`INSERT INTO users (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname,
                            u_lname, u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo,
                            u_photo, u_provice, u_city, u_address1, u_pincode, u_phone, u_email,
                            u_terms, u_status, u_collartype, created, modified, u_login_attempt,
                            u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
         VALUES (1, 0, 1, 0, 'sdefsolo@e2e.test', 'E2E', 'Sdef', MD5('x'), 'E2E Sdef Solo', 0, '',
                 '', '', 1, 1, '', '', '0000000000', 'sdefsolo@e2e.test', 1, 1, 0, NOW(), NOW(), 0,
                 NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');`);
  const solo = scalar("SELECT u_id FROM users WHERE u_comp_name = 'E2E Sdef Solo';");

  query(`INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode,
                            s_phone, s_skills, s_services, s_additional_details, s_status,
                            created, modified)
         VALUES (${solo}, 'E2E Sdef Solo Store', '1', ${ids.province}, ${ids.city}, 'x', 'x',
                 '0000000000', '${ids.skill}', '${ids.serviceA}', '${ids.detailA}', 1,
                 NOW(), NOW());`);
  const soloStore = scalar("SELECT s_id FROM store WHERE s_name = 'E2E Sdef Solo Store';");

  await page.goto('sadmin/postjobs/add');

  // The form asks for a store and nothing else, so every employer's stores are
  // in the one list - grouped under their owner, which is what tells two
  // branches of different chains that share a name apart.
  const groups = await page.locator('#p_store_id optgroup').evaluateAll(
    (els) => els.map((e) => e.getAttribute('label')));

  expect(groups).toContain('E2E Sdef Chain');
  expect(groups).toContain('E2E Sdef Solo');

  const under = (label) => page.locator(`#p_store_id optgroup[label="${label}"] option`)
    .evaluateAll((els) => els.map((e) => e.value).sort());

  expect(await under('E2E Sdef Chain')).toEqual([String(ids.storeOne), String(ids.storeTwo)].sort());
  expect(await under('E2E Sdef Solo')).toEqual([String(soloStore)]);

  // And picking one of them fills the form from that store, with the employer
  // never having been named.
  await page.selectOption('#p_store_id', String(soloStore));
  await expect.poll(() => ticked(page, 'p_additional_details')).toEqual([String(ids.detailA)]);
  expect(await ticked(page, 'p_skills')).toEqual([String(ids.skill)]);

  await expectNoServerError(page);
});

test('switching store replaces the defaults rather than adding to them', async ({ page }) => {
  await page.goto('sadmin/postjobs/add');
  await expect(page.locator(`#p_store_id option[value="${ids.storeTwo}"]`)).toHaveCount(1);

  await page.selectOption('#p_store_id', String(ids.storeOne));
  await expect.poll(() => ticked(page, 'p_services')).toEqual([String(ids.serviceA)]);

  await page.selectOption('#p_store_id', String(ids.storeTwo));
  await expect.poll(() => ticked(page, 'p_services')).toEqual([String(ids.serviceB)]);

  // Store two offers no software, so that group has to end up empty - not
  // still holding store one's answer.
  expect(await ticked(page, 'p_skills'), 'store one software must be gone').toEqual([]);
  expect(await ticked(page, 'p_additional_details')).toEqual([String(ids.detailB)]);

  await expectNoServerError(page);
});

test('changing them on a shift changes that shift only, never the store', async ({ page }) => {
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  query(`INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
       p_additional_details, p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES (${ids.owner}, ${ids.storeOne}, 'E2E Sdef Co', 'E2E-SDEF-1', 0,
       ${ids.province}, ${ids.city},
       ${shiftFor}, 30, 30, '01-09-2027', '2027-09-01', '09:00 - 17:00',
       '${ids.skill}', '${ids.serviceA}', '${ids.detailA}', 'Seeded by the end-to-end suite.',
       0, 1, 1, NOW(), NOW());`);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SDEF-1';");

  await page.goto(`sadmin/postjobs/edit/${shift}`);

  // Opening an existing shift must not re-copy the store's defaults over what
  // the shift already holds - the fill is bound to choosing a store.
  expect(await ticked(page, 'p_additional_details')).toEqual([String(ids.detailA)]);

  // Swap one detail for the other, for this shift.
  await page.click(`#cbg_p_additional_details label[for="cbg_p_additional_details_${ids.detailA}"]`);
  await page.click(`#cbg_p_additional_details label[for="cbg_p_additional_details_${ids.detailB}"]`);
  await page.click('button[name="savedata"], input[name="savedata"]');
  await settle(page);

  expect(scalar(`SELECT p_additional_details FROM post_job WHERE p_id = ${shift};`),
    'the shift took the change').toBe(String(ids.detailB));

  expect(scalar(`SELECT s_additional_details FROM store WHERE s_id = ${ids.storeOne};`),
    'the store default is untouched').toBe(String(ids.detailA));

  // And reopening shows the shift's own answer, not the store's.
  await page.goto(`sadmin/postjobs/edit/${shift}`);
  expect(await ticked(page, 'p_additional_details')).toEqual([String(ids.detailB)]);

  await expectNoServerError(page);
});

test('editing the store defaults leaves shifts already posted alone', async ({ page }) => {
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  query(`INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time, p_skills, p_services,
       p_additional_details, p_jobinfo, p_featured, p_status, p_approved, created, modified)
    VALUES (${ids.owner}, ${ids.storeOne}, 'E2E Sdef Co', 'E2E-SDEF-2', 0,
       ${ids.province}, ${ids.city},
       ${shiftFor}, 30, 30, '01-09-2027', '2027-09-01', '09:00 - 17:00',
       '${ids.skill}', '${ids.serviceA}', '${ids.detailA}', 'Seeded by the end-to-end suite.',
       0, 1, 1, NOW(), NOW());`);
  const shift = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SDEF-2';");

  await page.goto(`sadmin/stores/edit/${ids.storeOne}?owner=${ids.owner}`);
  await page.click(`#cbg_s_additional_details label[for="cbg_s_additional_details_${ids.detailA}"]`);
  await page.click(`#cbg_s_additional_details label[for="cbg_s_additional_details_${ids.detailB}"]`);
  await page.click('input[name="savedata"], button[name="savedata"]');
  await settle(page);

  expect(scalar(`SELECT s_additional_details FROM store WHERE s_id = ${ids.storeOne};`),
    'the new default took').toBe(String(ids.detailB));

  expect(scalar(`SELECT p_additional_details FROM post_job WHERE p_id = ${shift};`),
    'the posted shift kept what it had').toBe(String(ids.detailA));

  await expectNoServerError(page);
});
