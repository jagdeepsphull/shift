// @ts-check
/**
 * One employer login, several stores - change request B4.
 *
 * These are the three "Done when" lines from plan/change-requests.html#B4,
 * written as tests:
 *
 *   1. One login lists three stores and can post a shift against any of them.
 *   2. Every pre-existing shift still shows the address it always showed.
 *   3. The booked applicant sees the address of the correct store.
 *
 * The schema the tests drive is described in helpers/stores.js. If the
 * feature's tables are missing (e.g. the migration has not run), every test
 * skips with a message naming what is absent rather than failing.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { loginAsAgency, loginAsApplicant, seedShiftFixture, removeShiftFixture } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');
const {
  STORES,
  GROUP,
  STORE_LIST_URL,
  STORE_SELECT,
  multiStoreMissing,
  seedStores,
  removeStores,
  seedPharmacyGroup,
  removePharmacyGroup,
  storeIdOfShift,
} = require('../helpers/stores');

/**
 * The date the posting test files its shift under. The form has no title
 * field - the system names every shift PAS-<id> - so the tests find their
 * shift back by this date and the fixture agency's u_id.
 */
const SHIFT_DATE = '14-09-2027';

/** @type {Array<{name: string, number: string, address: string, phone: string, id: number}>} */
let stores = [];

/** @type {number} */
let agencyId;

test.beforeAll(() => {
  // Skipped runs must not touch the database at all: seedStores would insert
  // into a table that does not exist.
  if (multiStoreMissing()) return;

  ({ agencyId } = seedShiftFixture());
  stores = seedStores(agencyId);
  seedPharmacyGroup();
});

test.afterAll(() => {
  if (multiStoreMissing()) return;

  // The shift the posting test created (renamed PAS-<id> by the system), and
  // the application the booked-applicant test put on it.
  query(`
    DELETE FROM stu_saved_applied_jobs
     WHERE p_id IN (SELECT p_id FROM (
             SELECT p_id FROM post_job WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}') x);
  `);
  query(`DELETE FROM post_job WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}';`);
  removeStores();
  removePharmacyGroup();
  removeShiftFixture();
});

// One guard for the whole file, evaluated per test so the reason is reported
// against each of them rather than swallowing the file silently.
test.beforeEach(() => {
  const missing = multiStoreMissing();
  test.skip(missing !== null, missing || '');
});

/**
 * Fill everything on the shift form except the store, so it will submit.
 *
 * The Software and Details checkbox grids carry the HTML5 `required`
 * attribute; leaving them empty makes the browser refuse to submit, and the
 * test then fails on a page that never reached the server.
 *
 * @param {import('@playwright/test').Page} page
 */
async function fillShiftForm(page) {
  await page.selectOption('select[name="p_shift_for"]', { index: 1 });
  await page.fill('input[name="p_hourly_rate"]', '35');
  await page.fill('input[name="p_dates"]', SHIFT_DATE);
  await page.fill('input[name="p_shift_time"]', '09:00 - 17:00');

  // The boxes are Bootstrap custom controls: the input itself is covered by
  // its label, which is what a person clicks.
  for (const name of ['p_skills', 'p_services']) {
    const box = page.locator(`input[name="${name}[]"]`).first();
    if ((await box.count()) === 0) continue;

    await page.locator(`label[for="${await box.getAttribute('id')}"]`).click();
    await expect(box).toBeChecked();
  }
}

/**
 * The store-only fields on the registration form: the ones a multi-store owner
 * is not asked for, because they describe a location rather than the person.
 */
const STORE_ONLY_FIELDS = [
  '#u_l_provice', // Store Registration Province
  '#u_licence_no', // Store Number
  'textarea[name="u_address1"]', // Address
  '#provincelist', // Province
  '#city', // City
  'input[name="u_pincode"]', // Postal Code
];

test('registration asks each account type for the right fields', async ({ page }) => {
  await page.goto('front/register');
  await settle(page);

  const type = page.locator('#usrtpe');
  await expect(type.locator('option')).toContainText([
    '-- Select User Type --',
    'Manager',
    'Owner (Multi Store)',
    'Owner (Individual Store)',
    'Applicant',
  ]);

  // A manager and an individual owner both describe one location, so both are
  // asked for one, and the name field is that store's.
  for (const single of ['manager', 'owner_individual']) {
    await type.selectOption(single);
    await expect(page.locator('#compnamelbl')).toHaveText('Store Name');
    await expect(page.locator('#u_usersubtype'), 'not an applicant type').toBeHidden();
    for (const field of STORE_ONLY_FIELDS) {
      await expect(page.locator(field), `${field} is asked of ${single}`).toBeVisible();
    }
  }

  // A multi-store owner is not - their stores carry the address instead, and
  // the name field becomes the pharmacy's. A field left `required` while
  // hidden would make the browser refuse to submit without ever saying why,
  // so check the attribute went with the visibility.
  await type.selectOption('owner_multi');
  await expect(page.locator('#compnamelbl')).toHaveText('Pharmacy Name');
  await expect(page.locator('#u_comp_name')).toBeVisible();
  for (const field of STORE_ONLY_FIELDS) {
    const control = page.locator(field);
    await expect(control, `${field} is not asked of a multi-store owner`).toBeHidden();
    await expect(control, `${field} is not required while hidden`).not.toHaveAttribute('required', /.*/);
  }

  // An applicant keeps the fields it always had, and its own type.
  await type.selectOption('applicant');
  await expect(page.locator('#u_usersubtype')).toBeVisible();
  await expect(page.locator('#u_comp_name'), 'no store or pharmacy name').toBeHidden();
  await expect(page.locator('#liprov')).toHaveText('Applicant License Province');
  for (const field of STORE_ONLY_FIELDS) {
    await expect(page.locator(field), `${field} is asked of an applicant`).toBeVisible();
  }

  // Switching back restores them, required attributes and all.
  await type.selectOption('manager');
  await expect(page.locator('textarea[name="u_address1"]')).toHaveAttribute('required', /.*/);
  await expect(page.locator('#liprov')).toHaveText('Store Registration Province');

  await expectNoServerError(page);
});

test('only a manager is asked for a pharmacy group, and it is required', async ({ page }) => {
  // beforeAll seeds an approved multi-store owner, so the dropdown always has
  // something to list here. (With none at all it is not rendered, and a test
  // of it would pass by doing nothing.)
  await page.goto('front/register');
  await settle(page);

  const picker = page.locator('#u_parent_id');

  await page.selectOption('#usrtpe', 'manager');
  await expect(picker, 'a manager runs a store for a group').toBeVisible();
  await expect(picker, 'and must say which').toHaveAttribute('required', /.*/);
  await expect(picker, 'nothing chosen to begin with').toHaveValue('');

  // Every option is an approved multi-store owner, by pharmacy name.
  const offered = (await picker.locator('option').allTextContents())
    .map((t) => t.trim())
    .filter((t) => t !== '-- None --');

  const real = query(`
    SELECT u_comp_name FROM users
     WHERE u_usertype = 1 AND u_emp_role = 1 AND u_status = 1 ORDER BY u_comp_name ASC;
  `).split('\n').filter((t) => t !== '');

  expect(offered, 'the list is exactly the approved pharmacy groups').toEqual(real);
  expect(offered, 'including the seeded one').toContain(GROUP.name);

  // Nobody else answers to a group. A select left `required` while hidden
  // would block the form without ever saying why, so the attribute has to go
  // with the visibility.
  for (const other of ['owner_multi', 'owner_individual', 'applicant']) {
    await page.selectOption('#usrtpe', other);
    await expect(picker, `${other} has no pharmacy group`).toBeHidden();
    await expect(picker, `${other} is not held up by a hidden required field`)
      .not.toHaveAttribute('required', /.*/);
  }

  await expectNoServerError(page);
});

test('one login lists all three of its stores', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto(STORE_LIST_URL);
  await settle(page);

  const list = page.locator('#storelist tbody');

  for (const store of STORES) {
    await expect(list, `${store.name} belongs to this login and should be listed`).toContainText(
      store.name,
    );
    await expect(list).toContainText(store.number);
  }

  await expectNoServerError(page);
});

test('another employer cannot see this login\'s stores', async ({ page }) => {
  // The store list is scoped to the logged-in owner, the same way every other
  // employer screen is scoped by u_id. Every employer has at least one store
  // (the migration made one from each login), so any other employer will do.
  const otherId = Number(
    scalar(`SELECT u_id FROM users WHERE u_usertype = 1 AND u_id <> ${agencyId} LIMIT 1;`) || 0,
  );
  test.skip(otherId === 0, 'no second employer account to check the scoping against');

  await loginAsAgency(page);
  await page.goto(STORE_LIST_URL);
  await settle(page);

  const rows = await page.locator('#storelist tbody tr').count();
  expect(rows, 'only this login\'s three stores are listed').toBe(STORES.length);

  const foreign = scalar(`SELECT s_name FROM store WHERE u_id = ${otherId} LIMIT 1;`);
  test.skip(foreign === '', 'the other employer has no stores to leak');

  await expect(page.locator('#storelist tbody')).not.toContainText(foreign);
});

test('a shift can be posted against a chosen store', async ({ page }) => {
  const chosen = stores[1]; // deliberately not the first, so a default passes nothing

  await loginAsAgency(page);
  await page.goto('employer/post_job');
  await settle(page);

  const picker = page.locator(STORE_SELECT);
  await expect(picker, 'the shift form offers a store to post against').toBeVisible();

  // Every store this login owns is offered (the picker lists them A-Z), not
  // just the one on the users row.
  await expect(picker.locator('option')).toContainText(
    STORES.map((s) => s.name).sort(),
  );

  await picker.selectOption(String(chosen.id));
  await fillShiftForm(page);

  await Promise.all([
    page.waitForLoadState('load'),
    page.locator('[name="savepostjob"]').click(),
  ]);
  await settle(page);
  await expectNoServerError(page);

  // The shift is stored against the chosen store, not the login's own address.
  // post_job() renames the shift to PAS-<id>, so find it by the row it created.
  const stored = Number(
    scalar(
      `SELECT p_store_id FROM post_job
        WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}' ORDER BY p_id DESC LIMIT 1;`,
    ) || 0,
  );
  expect(stored, 'the shift records the store it was posted against').toBe(chosen.id);
});

test('editing a shift keeps its store and can move it to another one', async ({ page }) => {
  const pid = Number(
    scalar(
      `SELECT p_id FROM post_job WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}'
        ORDER BY p_id DESC LIMIT 1;`,
    ) || 0,
  );
  test.skip(pid === 0, 'the posting test did not leave a shift behind');

  await loginAsAgency(page);
  await page.goto(`employer/edit_job/${pid}`);
  await settle(page);

  const picker = page.locator(STORE_SELECT);
  await expect(picker, 'the edit form offers the store picker too').toBeVisible();
  await expect(picker, 'it opens on the store the shift was posted against').toHaveValue(
    String(stores[1].id),
  );

  await picker.selectOption(String(stores[2].id));
  await Promise.all([
    page.waitForLoadState('load'),
    page.locator('[name="savepostjob"]').click(),
  ]);
  await settle(page);

  expect(
    Number(scalar(`SELECT p_store_id FROM post_job WHERE p_id = ${pid};`) || 0),
    'the shift moved to the store chosen on edit',
  ).toBe(stores[2].id);
  await expectNoServerError(page);
});

test('a shift posted before B4 still shows the address it always showed', async ({ page }) => {
  // seedShiftFixture inserts shifts with no store reference at all
  // (p_store_id 0) - exactly what a pre-migration row looks like. Those fall
  // back to the owner's login columns, which is where the address lived until
  // B4 - so the page shows what it always showed.
  expect(storeIdOfShift('E2E-SHIFT-A'), 'the fixture shift has no store reference').toBe(0);

  const pid = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SHIFT-A';");

  await page.goto(`front/job_detail/${pid}`);
  await settle(page);

  // '12 Fixture Lane' is the address seeded onto the agency login.
  await expect(page.locator('body')).toContainText('12 Fixture Lane');
  await expectNoServerError(page);
});

test('the booked applicant sees the chosen store\'s address, not another store\'s', async ({ page }) => {
  const pid = Number(
    scalar(
      `SELECT p_id FROM post_job WHERE u_id = ${agencyId} AND p_dates = '${SHIFT_DATE}'
        ORDER BY p_id DESC LIMIT 1;`,
    ) || 0,
  );
  test.skip(pid === 0, 'the posting test did not leave a shift behind');

  // Whichever store the shift is at by now - the edit test above moves it -
  // so this test asserts on the shift's real location rather than assuming
  // the order the file ran in.
  const storeId = Number(scalar(`SELECT p_store_id FROM post_job WHERE p_id = ${pid};`) || 0);
  const chosen = stores.find((s) => s.id === storeId);
  expect(chosen, 'the shift is at one of the seeded stores').toBeTruthy();

  // Put the applicant on the shift and book them, rather than driving the whole
  // apply -> shortlist -> approve journey again: that path has its own spec.
  const applicantId = scalar(
    "SELECT u_id FROM users WHERE u_userid = 'e2e.pharmacist@example.com';",
  );
  query(`
    INSERT INTO stu_saved_applied_jobs
      (u_id, agency_id, p_id, sj_applied_date, sj_status, sj_is_approved,
       sj_admin_comment, sj_applied_desc, sj_resubmit_comments, sj_rejected_comments,
       created, modified)
    VALUES
      (${applicantId}, ${agencyId}, ${pid}, NOW(), 1, 1, '', '', '', 0, NOW(), NOW());
  `);

  await loginAsApplicant(page);
  await page.goto(`front/job_detail/${pid}`);
  await settle(page);

  const body = page.locator('body');
  await expect(body, 'the booked applicant sees the store they are working at').toContainText(
    chosen.address,
  );
  await expect(body).toContainText(chosen.phone);

  // and not either of the other two stores on the same login
  for (const other of stores.filter((s) => s.id !== chosen.id)) {
    await expect(body, `${other.name} is a different location`).not.toContainText(other.address);
    await expect(body).not.toContainText(other.phone);
  }

  await expectNoServerError(page);
});
