// @ts-check
/**
 * The employer's own shift form asks for what the back-office one asks for.
 *
 * The Additional Details master (`additional_details`) landed on the two admin
 * shift forms and was never added to the two employer ones, so a shift posted
 * by the employer themselves could not carry any - the column simply stayed
 * empty. These pin the third tick-box group onto both employer forms, and the
 * name of the free-text box beside it: "Additional details for agency" rather
 * than "Additional details", which read as a second label for the group above.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { seedShiftFixture, removeShiftFixture, loginAsAgency } = require('../helpers/front');
const { seedStores, removeStores, multiStoreMissing, STORE_SELECT } = require('../helpers/stores');
const { query, scalar } = require('../helpers/db');

const DETAIL = 'E2E Form Detail';
const DETAIL_OFF = 'E2E Form Detail Deactivated';

/** Far enough out that no real shift shares it, so cleanup can find ours. */
const SHIFT_DATE = '18-10-2027';

/** @type {number} */
let agencyId;

/** @type {Array<{name: string, number: string, address: string, phone: string, id: number}>} */
let stores = [];

/** @type {string} */
let detailId;

function removeDetails() {
  query("DELETE FROM additional_details WHERE ad_name LIKE 'E2E Form Detail%';");
}

test.beforeAll(() => {
  if (multiStoreMissing()) return;

  ({ agencyId } = seedShiftFixture());
  // A shift is posted against a store, so the fixture agency needs one.
  stores = seedStores(agencyId);

  removeDetails();
  query(`INSERT INTO additional_details (ad_name, ad_status) VALUES ('${DETAIL}', 1);`);
  query(`INSERT INTO additional_details (ad_name, ad_status) VALUES ('${DETAIL_OFF}', 0);`);
  detailId = scalar(`SELECT ad_id FROM additional_details WHERE ad_name = '${DETAIL}';`);
});

test.afterAll(() => {
  if (multiStoreMissing()) return;

  // Shifts first: removeShiftFixture takes the agency row they hang off.
  query(`DELETE FROM post_job WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}';`);
  removeStores();
  removeShiftFixture();
  removeDetails();
});

test.beforeEach(() => {
  const missing = multiStoreMissing();
  test.skip(missing !== null, missing || '');
});

/**
 * The label sitting above the free-text box, whatever it says.
 *
 * Found through the box rather than by its own text: the tick-box group above
 * it is called "Additional Details" too, so matching on words would find both.
 *
 * @param {import('@playwright/test').Page} page
 */
const jobinfoLabel = (page) =>
  page.locator('.form-group', { has: page.locator('#p_jobinfo') }).locator('label').first();

/**
 * Fill the shift form except the additional details, and choose a store.
 *
 * @param {import('@playwright/test').Page} page
 */
async function fillShiftForm(page) {
  const store = page.locator(STORE_SELECT);
  await store.selectOption(String(stores[0].id));

  await page.selectOption('select[name="p_shift_for"]', { index: 1 });
  await page.fill('input[name="p_hourly_rate"]', '35');

  // Through the widget, not the input: typing into the date box re-opens the
  // calendar, which writes its own idea of the value back over what was typed.
  await page.evaluate((dmy) => {
    const [d, m, y] = dmy.split('-').map(Number);
    window.jQuery('input[name="p_dates"]').datepicker('setDate', new Date(y, m - 1, d));
  }, SHIFT_DATE);
  await expect(page.locator('input[name="p_dates"]')).toHaveValue(SHIFT_DATE);

  // The time picker fills itself in with the coming hour - a valid shift time,
  // and all this form needs.
  await expect(page.locator('input[name="p_shift_time"]')).not.toHaveValue('');

  // Software is the one required group, and the form's own guard refuses to
  // submit while it is empty. Details is ticked too because this fixture is
  // meant to look like a real shift, not because the form insists on it.
  for (const name of ['p_skills', 'p_services']) {
    const box = page.locator(`input[name="${name}[]"]`).first();
    await page.locator(`label[for="${await box.getAttribute('id')}"]`).click();
    await expect(box).toBeChecked();
  }
}

test('the employer shift form offers the Additional Details group, as the back office does', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/post_job');
  await settle(page);

  const grid = page.locator('#cbg_p_additional_details');
  await expect(grid, 'the group the admin form has').toBeVisible();

  // Beside Software and Details, not instead of them.
  await expect(page.locator('#cbg_p_skills')).toBeVisible();
  await expect(page.locator('#cbg_p_services')).toBeVisible();

  await expect(grid.locator(`input[value="${detailId}"]`)).toHaveCount(1);
  await expect(grid, 'a deactivated row is not offered').not.toContainText(DETAIL_OFF);

  // Optional, and so is Details beside it: a shift that offers nothing out of
  // the ordinary is a real shift. Software is the only group that must hold
  // something.
  await expect(grid).toHaveAttribute('data-required', '0');
  await expect(page.locator('#cbg_p_services'), 'Details must not be mandatory')
    .toHaveAttribute('data-required', '0');
  await expect(page.locator('#cbg_p_skills'), 'Software still is')
    .toHaveAttribute('data-required', '1');

  // And the free-text box below is named apart from the group.
  await expect(jobinfoLabel(page)).toHaveText('Additional details for agency');

  await expectNoServerError(page);
});

test('the employer edit form offers it too, and names the free-text box the same way', async ({ page }) => {
  const pid = scalar(`
    SELECT p_id FROM post_job
     WHERE u_id = ${agencyId} AND p_status = 0 AND p_approved = 0 ORDER BY p_id DESC LIMIT 1;
  `);

  // No editable shift yet on a fresh database - make one to open.
  const shift = pid || seedShift('');

  await loginAsAgency(page);
  await page.goto(`employer/edit_job/${shift}`);
  await settle(page);

  await expect(page.locator('#cbg_p_additional_details')).toBeVisible();
  await expect(jobinfoLabel(page)).toHaveText('Additional details for agency');
  await expectNoServerError(page);
});

test('a shift the employer posts keeps the additional details that were ticked', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/post_job');
  await settle(page);

  await fillShiftForm(page);
  await page.click(`#cbg_p_additional_details label[for="cbg_p_additional_details_${detailId}"]`);

  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savepostjob"]'),
  ]);
  await settle(page);
  await expectNoServerError(page);

  const stored = scalar(`
    SELECT p_additional_details FROM post_job
     WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}' ORDER BY p_id DESC LIMIT 1;
  `);

  expect(stored, 'the ticked detail is on the shift').toBe(String(detailId));
});

test('the employer edit form brings them back ticked, and can clear them', async ({ page }) => {
  const shift = seedShift(String(detailId));

  await loginAsAgency(page);
  await page.goto(`employer/edit_job/${shift}`);
  await settle(page);

  const box = page.locator(`#cbg_p_additional_details input[value="${detailId}"]`);
  await expect(box, 'what was stored comes back ticked').toBeChecked();

  // Unticking the last box clears the column rather than leaving the old value
  // behind - nothing at all is posted for an all-clear group.
  await page.click(`#cbg_p_additional_details label[for="cbg_p_additional_details_${detailId}"]`);

  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savepostjob"]'),
  ]);
  await settle(page);

  expect(scalar(`SELECT p_additional_details FROM post_job WHERE p_id = ${shift};`)).toBe('');
  await expectNoServerError(page);
});

/**
 * A shift owned by the fixture agency, editable (pending and unapproved).
 *
 * @param {string} details comma separated additional_details.ad_id list
 * @returns {string} post_job.p_id
 */
function seedShift(details) {
  const skill = scalar('SELECT ss_id FROM software_skills WHERE ss_status = 1 LIMIT 1;');
  const service = scalar('SELECT st_id FROM store_service WHERE st_status = 1 LIMIT 1;');
  const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 LIMIT 1;');

  query(`
    INSERT INTO post_job
      (u_id, p_store_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
       p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
       p_skills, p_services, p_additional_details, p_jobinfo, p_featured, p_status, p_approved,
       created, modified)
    VALUES
      (${agencyId}, ${stores[0].id}, 'E2E Pharmacy', 'E2E-FORM-1', 0,
       (SELECT p_id FROM province ORDER BY p_id LIMIT 1), (SELECT c_id FROM city ORDER BY c_id LIMIT 1),
       ${shiftFor}, 30, 30, '${SHIFT_DATE}', '2027-10-18', '09:00 - 17:00',
       '${skill}', '${service}', '${details}', 'Seeded by the end-to-end suite.', 0, 0, 0, NOW(), NOW());
  `);

  return scalar(`
    SELECT p_id FROM post_job WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}' ORDER BY p_id DESC LIMIT 1;
  `);
}
