// @ts-check
/**
 * "Agreement Done" on the four back-office user forms.
 *
 * Whether the signed agreement is on file is a fact the office holds, not
 * something an account holder tells us: it is ticked on applicant add/edit and
 * employer add/edit and nowhere else, so those four screens are the only place
 * `users.u_agreement_done` is ever written.
 *
 * The case worth a test is UNTICKING. A clear checkbox posts nothing at all, so
 * the save paths read the answer from whether the field arrived rather than
 * from its value; a version that read the value would let the box be ticked and
 * never cleared, and the mistake would look like the form ignoring the click.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError, settle, filterTable } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';

const NEW_APPLICANT = 'e2e.agreement.applicant@example.com';
const NEW_MANAGER = 'e2e.agreement.manager@example.com';
const GROUP = 'e2e.agreement.group@example.com';
const OWNER = 'e2e.agreement.owner@example.com';
const APPLICANT = 'e2e.agreement.seeded@example.com';

/** @type {Record<string, number>} */
const ids = {};

let PROVINCE = '0';
let CITY = '0';

/**
 * The names are letters only on purpose: both edit forms apply the same
 * NAME_PATTERN rule the public forms do, so a fixture called "E2E" is refused
 * the moment the form is re-saved - and the refusal looks exactly like the
 * checkbox being ignored.
 *
 * @param {string} email
 * @param {{usertype: number, role: number, parent: number, company: string, agreement: number}} opts
 * @returns {number}
 */
function seedUser(email, opts) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       u_agreement_done, created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress,
       reset_token, token_expiry)
    VALUES
      (${opts.usertype}, 0, ${opts.role}, ${opts.parent}, '${email}', 'Agreement', 'Fixture', MD5('${PASSWORD}'),
       '${opts.company}', ${PROVINCE}, 'E2E-LIC', '', '', ${PROVINCE}, ${CITY},
       '4 Agreement Street', 'M5A 1A1', '4165550404', '${email}', 1, 1, 0,
       ${opts.agreement}, NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${email}';`));
}

function removeSeeded() {
  query(`
    DELETE FROM store
     WHERE u_id IN (SELECT u_id FROM (SELECT u_id FROM users WHERE u_email LIKE 'e2e.agreement.%@example.com') x);
  `);
  query(`DELETE FROM users WHERE u_email LIKE 'e2e.agreement.%@example.com' OR u_userid LIKE 'e2e.agreement.%@example.com';`);
}

test.beforeAll(() => {
  removeSeeded();

  PROVINCE = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;') || '0';
  CITY = scalar(`SELECT c_id FROM city WHERE c_status = 1 AND c_province = ${PROVINCE} LIMIT 1;`) || '0';

  // A multi-store owner with a location, so the manager add form has a group to
  // offer and a store under it to pick.
  ids.group = seedUser(GROUP, { usertype: 1, role: 1, parent: 0, company: 'E2E Agreement Group', agreement: 0 });
  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${ids.group}, 'E2E Agreement Group Store', 'E2E-NO', ${PROVINCE}, ${CITY}, '4 Agreement Street', 'M5A 1A1', '4165550404', 1);
  `);

  // Two accounts that already have the agreement on file, for the untick tests.
  ids.owner = seedUser(OWNER, { usertype: 1, role: 1, parent: 0, company: 'E2E Agreement Owner', agreement: 1 });
  ids.applicant = seedUser(APPLICANT, { usertype: 2, role: 0, parent: 0, company: '', agreement: 1 });
});

test.afterAll(() => {
  removeSeeded();
});

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

/**
 * AdminLTE's custom-control draws the tick itself and leaves the real input
 * invisible under its label, so the label is what a person actually clicks -
 * and what Playwright has to click too. Same approach as the shift form's
 * recipient boxes.
 *
 * @param {import('@playwright/test').Page} page
 */
const toggle = (page) => page.click('label[for="u_agreement_done"]');

/**
 * @param {import('@playwright/test').Page} page
 */
async function save(page) {
  await settle(page);
  await Promise.all([
    page.waitForLoadState('load'),
    page.click('input[name="savedata"]'),
  ]);
  await settle(page);
}

/**
 * The province/city pair on the applicant form is a dependent dropdown - the
 * city list arrives over ajax once a province is chosen.
 *
 * @param {import('@playwright/test').Page} page
 */
async function chooseProvinceAndCity(page) {
  const province = page.locator('select[name="u_provice"]');
  const cityLoaded = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));
  await province.selectOption(PROVINCE);
  await cityLoaded;

  await page.locator('select[name="u_city"]').selectOption(CITY);
  await page.locator('select[name="u_l_provice"]').selectOption(PROVINCE);
}

test('the box is on all four forms, and starts clear on both add forms', async ({ page }) => {
  for (const url of [
    'sadmin/applicant/add',
    'sadmin/employer/add?kind=owner',
    'sadmin/employer/add?kind=manager',
  ]) {
    await page.goto(url);
    await expectNoServerError(page);

    const box = page.locator('input[name="u_agreement_done"]');
    await expect(box).toHaveCount(1);
    await expect(box).not.toBeChecked();
    await expect(page.locator('label[for="u_agreement_done"]')).toHaveText('Agreement Done');
  }

  // ...and reflects what is stored on both edit forms.
  await page.goto(`sadmin/applicant/edit/${ids.applicant}`);
  await expect(page.locator('input[name="u_agreement_done"]')).toBeChecked();

  await page.goto(`sadmin/employer/edit/${ids.group}?kind=owner`);
  await expect(page.locator('input[name="u_agreement_done"]')).not.toBeChecked();
});

test('ticking it on the applicant add form saves it', async ({ page }) => {
  await page.goto('sadmin/applicant/add');

  await page.fill('input[name="u_fname"]', 'Agreement');
  await page.fill('input[name="u_lname"]', 'Applicant');

  const subtype = page.locator('select[name="u_usersubtype"]');
  const subtypeValue = await subtype.locator('option:not([value=""])').first().getAttribute('value');
  await subtype.selectOption(String(subtypeValue));

  await page.fill('input[name="u_licence_no"]', 'E2E-LIC-A');
  await page.fill('input[name="u_email"]', NEW_APPLICANT);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550405');
  await page.fill('textarea[name="u_address1"]', '5 Agreement Street');
  await chooseProvinceAndCity(page);
  await page.fill('input[name="u_pincode"]', 'M5A 1A1');
  await page.selectOption('select[name="u_status"]', '1');
  await toggle(page);

  await save(page);

  expect(count('users', `u_email = '${NEW_APPLICANT}'`)).toBe(1);
  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_email = '${NEW_APPLICANT}';`)).toBe('1');
});

test('ticking it on the manager add form saves it', async ({ page }) => {
  await page.goto('sadmin/employer/add?kind=manager');
  await expectNoServerError(page);

  const storesLoaded = page.waitForResponse((r) => r.url().includes('ajax_getstorelist'));
  await page.selectOption('select[name="u_parent_id"]', String(ids.group));
  await storesLoaded;

  const store = page.locator('select[name="u_store_id"]');
  const storeId = await store.locator('option:not([value=""])').first().getAttribute('value');
  await store.selectOption(String(storeId));

  await page.fill('input[name="u_fname"]', 'Agreement');
  await page.fill('input[name="u_lname"]', 'Manager');
  await page.fill('input[name="u_email"]', NEW_MANAGER);
  await page.fill('input[name="u_password"]', PASSWORD);
  await page.fill('input[name="u_phone"]', '4165550406');
  await page.selectOption('select[name="u_status"]', '1');
  await toggle(page);

  await save(page);

  expect(count('users', `u_email = '${NEW_MANAGER}'`)).toBe(1);
  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_email = '${NEW_MANAGER}';`)).toBe('1');
});

test('clearing it on the applicant edit form puts it back to 0', async ({ page }) => {
  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_id = ${ids.applicant};`)).toBe('1');

  await page.goto(`sadmin/applicant/edit/${ids.applicant}`);
  await expectNoServerError(page);

  const box = page.locator('input[name="u_agreement_done"]');
  await expect(box).toBeChecked();
  await toggle(page);

  await save(page);

  // The whole point: a clear box posts nothing, so this only works because the
  // save reads the field's absence rather than its value.
  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_id = ${ids.applicant};`)).toBe('0');
  await page.goto(`sadmin/applicant/edit/${ids.applicant}`);
  await expect(page.locator('input[name="u_agreement_done"]')).not.toBeChecked();
});

test('the employer edit form ticks and unticks it', async ({ page }) => {
  await page.goto(`sadmin/employer/edit/${ids.group}?kind=owner`);
  await expectNoServerError(page);

  await toggle(page);
  await save(page);
  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_id = ${ids.group};`)).toBe('1');

  await page.goto(`sadmin/employer/edit/${ids.group}?kind=owner`);
  await toggle(page);
  await save(page);
  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_id = ${ids.group};`)).toBe('0');
});

test('saving a form without touching the box leaves the account as it was', async ({ page }) => {
  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_id = ${ids.owner};`)).toBe('1');

  await page.goto(`sadmin/employer/edit/${ids.owner}?kind=owner`);
  await expectNoServerError(page);
  await save(page);

  expect(scalar(`SELECT u_agreement_done FROM users WHERE u_id = ${ids.owner};`)).toBe('1');
});

/**
 * Read the Agreement Done cell of one row of the list on screen.
 *
 * By the badge's own words rather than by column number: the four listings put
 * it at four different positions, and the responsive plugin moves cells about
 * besides. Nothing else in these rows says just "Yes" or "No" - Status spells
 * Active and Pending, the store Links say Map pin and Website.
 *
 * The search box first, because the lists page at ten rows and a fixture sorted
 * under E is rarely on the first one.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} rowText what identifies the row - an e-mail or a store name
 * @param {string} expected 'Yes' or 'No'
 */
async function expectAgreementCell(page, rowText, expected) {
  await expect(page.locator('#example1 thead th').filter({ hasText: 'Agreement Done' })).toHaveCount(1);

  await filterTable(page, rowText);

  const row = page.locator('#example1 tbody tr', { hasText: rowText });
  await expect(row).toHaveCount(1);
  await expect(row.locator('span.badge').filter({ hasText: /^(Yes|No)$/ })).toHaveText(expected);
}

/**
 * What the database says right now, as the word the list should be showing.
 *
 * Asked per assertion rather than hard-coded, because the tests above leave
 * these fixtures ticked or clear depending on how far the file has run.
 *
 * @param {number} uid
 */
const storedWord = (uid) => (scalar(`SELECT u_agreement_done FROM users WHERE u_id = ${uid};`) === '1' ? 'Yes' : 'No');

test('the applicant list carries the column', async ({ page }) => {
  await page.goto('sadmin/applicant');
  await expectNoServerError(page);

  await expectAgreementCell(page, APPLICANT, storedWord(ids.applicant));
});

test('both employer lists carry the column', async ({ page }) => {
  for (const url of ['sadmin/employer', 'sadmin/employer/owner']) {
    await page.goto(url);
    await expectNoServerError(page);

    await expectAgreementCell(page, OWNER, storedWord(ids.owner));
  }
});

test('the store list carries the owning employer answer', async ({ page }) => {
  // A store has no agreement of its own: the column reports the account that
  // holds it, which is why this asserts the group's value against a store row.
  await page.goto('sadmin/stores');
  await expectNoServerError(page);

  await expectAgreementCell(page, 'E2E Agreement Group Store', storedWord(ids.group));
});
