// @ts-check
/**
 * The hours a new shift opens on.
 *
 * Both "add" forms left Shift Time to the picker, and the picker opened on the
 * coming hour - so a shift added at twenty past four proposed 16:15 - 17:15,
 * and every one of them had to be corrected by hand. They now open on a nine
 * to six day, which is what most shifts are.
 *
 * The two halves of that are worth pinning separately. The hours themselves are
 * one; the other is that this is a default and not an override - the edit forms
 * still show the hours the shift was saved with, and a save that comes back
 * rejected still shows the hours that were typed. A default that quietly won
 * over either would lose real times.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { seedShiftFixture, removeShiftFixture, loginAsAgency } = require('../helpers/front');
const { seedStores, removeStores, multiStoreMissing, STORE_SELECT } = require('../helpers/stores');
const { query, scalar } = require('../helpers/db');

/** What both add forms are expected to propose. */
const DEFAULT_TIME = '09:00 - 18:00';

/**
 * Hours no default would arrive at, on a shift the employer may still edit.
 *
 * Deliberately not the fixture's own 09:00 - 17:00 either: this shift is what
 * the edit forms are read through, and a value shared with anything else would
 * let a mixed-up shift pass the assertion.
 */
const SAVED_TIME = '07:30 - 14:45';

/** Far enough out that no real shift shares it, so cleanup can find ours. */
const SHIFT_DATE = '11-11-2027';

const TITLE = 'E2E-TIMEDEF';

/** @type {number} */
let agencyId;

/** @type {Array<{name: string, number: string, address: string, phone: string, id: number}>} */
let stores = [];

/** The employer-owned shift, still open for editing (p_status/p_approved 0). */
let draftShift = '';

test.beforeAll(() => {
  if (multiStoreMissing()) return;

  ({ agencyId } = seedShiftFixture());
  // A shift is posted against a store, so the fixture agency needs one.
  stores = seedStores(agencyId);

  query(`
    INSERT INTO post_job
      (u_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_store_id, p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
       p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved,
       created, modified)
    VALUES
      (${agencyId}, 'E2E Pharmacy', '${TITLE}', 0,
       (SELECT p_id FROM province WHERE p_status = 1 LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 LIMIT 1),
       (SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1),
       ${stores[0].id}, 30, 30, '${SHIFT_DATE}', '2027-11-11', '${SAVED_TIME}',
       '', '', 'Seeded by the end-to-end suite.', 0, 0, 0,
       NOW(), NOW());
  `);

  draftShift = scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${TITLE}';`);
});

test.afterAll(() => {
  if (multiStoreMissing()) return;

  query(`DELETE FROM post_job WHERE p_job_title = '${TITLE}';`);
  removeStores();
  removeShiftFixture();
});

test.beforeEach(() => {
  const missing = multiStoreMissing();
  test.skip(missing !== null, missing || '');
});

const shiftTime = (page) => page.locator('input[name="p_shift_time"]');

/*
 * Both screens a role can reach are read under one login rather than one each.
 * Signing in is the flakiest thing these tests do - the verification image is
 * asked for twice on the login page and the session keeps whichever answered
 * last - so a spec that signs in five times is mostly testing the login.
 */

test('the admin add form opens on a nine to six day, and edit keeps what was saved', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('sadmin/postjobs/add');
  await settle(page);

  // After settle(), so this is the value the picker has finished with rather
  // than the one PHP printed: the picker writes itself back over the box on
  // init, and a default it cannot parse would be replaced by the current hour.
  await expect(shiftTime(page)).toHaveValue(DEFAULT_TIME);

  await page.goto(`sadmin/postjobs/edit/${draftShift}`);
  await settle(page);

  await expect(shiftTime(page), 'the shift keeps its own hours').toHaveValue(SAVED_TIME);

  await expectNoServerError(page);
});

test('the employer add form opens on the same hours, and edit keeps what was saved', async ({ page }) => {
  await loginAsAgency(page);

  await page.goto('employer/post_job');
  await settle(page);

  await expect(shiftTime(page)).toHaveValue(DEFAULT_TIME);

  await page.goto(`employer/edit_job/${draftShift}`);
  await settle(page);

  await expect(shiftTime(page), 'the shift keeps its own hours').toHaveValue(SAVED_TIME);

  await expectNoServerError(page);
});

test('the default is what gets saved when it is left alone', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/post_job');
  await settle(page);

  await page.locator(STORE_SELECT).selectOption(String(stores[0].id));
  await page.selectOption('select[name="p_shift_for"]', { index: 1 });
  await page.fill('input[name="p_hourly_rate"]', '35');

  // Through the widget, not the input: typing into the date box re-opens the
  // calendar, which writes its own idea of the value back over what was typed.
  await page.evaluate((dmy) => {
    const [d, m, y] = dmy.split('-').map(Number);
    window.jQuery('input[name="p_dates"]').datepicker('setDate', new Date(y, m - 1, d));
  }, SHIFT_DATE);
  await expect(page.locator('input[name="p_dates"]')).toHaveValue(SHIFT_DATE);

  // Software is the one required group, and the form's own guard refuses to
  // submit while it is empty.
  const box = page.locator('input[name="p_skills[]"]').first();
  await page.locator(`label[for="${await box.getAttribute('id')}"]`).click();
  await expect(box).toBeChecked();

  // Shift Time is not touched at all - the point of the test.
  await page.click('input[name="savepostjob"], button[name="savepostjob"]');
  await settle(page);
  await expectNoServerError(page);

  const saved = scalar(
    `SELECT p_shift_time FROM post_job
      WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}' AND p_job_title <> '${TITLE}'
      ORDER BY p_id DESC LIMIT 1;`,
  );
  expect(saved, 'the proposed hours are posted like any other value').toBe(DEFAULT_TIME);

  query(`DELETE FROM post_job
          WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}' AND p_job_title <> '${TITLE}';`);
});
