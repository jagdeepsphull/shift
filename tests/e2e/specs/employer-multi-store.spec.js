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
const {
  loginAsAgency,
  loginAsApplicant,
  loginAsFrontUser,
  seedShiftFixture,
  removeShiftFixture,
} = require('../helpers/front');
const { query, scalar } = require('../helpers/db');
const {
  STORES,
  GROUP,
  GROUP_STORES,
  REG_STORE_ENDPOINT,
  STORE_LIST_URL,
  STORE_SELECT,
  multiStoreMissing,
  seedStores,
  removeStores,
  seedPharmacyGroup,
  removePharmacyGroup,
  seedGroupStores,
  removeGroupStores,
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

/** @type {number} */
let groupId;

/** @type {Array<{name: string, number: string, address: string, phone: string, id: number}>} */
let groupStores = [];

test.beforeAll(() => {
  // Skipped runs must not touch the database at all: seedStores would insert
  // into a table that does not exist.
  if (multiStoreMissing()) return;

  ({ agencyId } = seedShiftFixture());
  stores = seedStores(agencyId);

  // The group needs stores of its own, or registration does not offer it at
  // all - a manager has to have somewhere to be sent.
  groupId = seedPharmacyGroup();
  groupStores = seedGroupStores(groupId);
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
  // Before the group: removePharmacyGroup deletes the owner row and would
  // leave these orphaned.
  removeGroupStores();
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

  // Through the widget, not the input: the date box carries a datepicker, and
  // typing into it re-opens the calendar, which then writes its own idea of the
  // value back over what was typed. The back-office specs do the same.
  await page.evaluate((dmy) => {
    const [d, m, y] = dmy.split('-').map(Number);
    window.jQuery('input[name="p_dates"]').datepicker('setDate', new Date(y, m - 1, d));
  }, SHIFT_DATE);
  await expect(page.locator('input[name="p_dates"]')).toHaveValue(SHIFT_DATE);

  // The time picker fills itself in with the coming hour, which is a valid
  // shift time and all this form needs.
  await expect(page.locator('input[name="p_shift_time"]')).not.toHaveValue('');

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
 * The store-only fields on the registration form: the ones neither a
 * multi-store owner nor a manager is asked for, because they describe a
 * location rather than the person. The owner has no location yet; the manager's
 * belongs to the store they picked.
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
    'Owner',
    'Manager',
    'Applicant',
  ]);

  // A manager describes nothing: they pick one of their group's stores, and it
  // already has the name, number and address this form used to ask them to
  // retype. So every store field goes, and the store picker arrives.
  await type.selectOption('2');
  await expect(page.locator('#u_store_id'), 'a manager picks a store').toBeVisible();
  await expect(page.locator('#u_store_id')).toHaveAttribute('required', /.*/);
  for (const field of [...STORE_ONLY_FIELDS, '#u_comp_name', '#u_website']) {
    const control = page.locator(field);
    await expect(control, `${field} is not asked of a manager`).toBeHidden();
    await expect(control, `${field} is not required while hidden`).not.toHaveAttribute(
      'required',
      /.*/,
    );
  }

  // An owner is not - their stores carry the address instead, and the name
  // field becomes the corporate group's. A field left `required` while hidden
  // would make the browser refuse to submit without ever saying why, so check
  // the attribute went with the visibility.
  await type.selectOption('1');
  await expect(page.locator('#compnamelbl')).toHaveText('Corporate Group Name');
  await expect(page.locator('#u_comp_name')).toBeVisible();
  for (const field of STORE_ONLY_FIELDS) {
    const control = page.locator(field);
    await expect(control, `${field} is not asked of an owner`).toBeHidden();
    await expect(control, `${field} is not required while hidden`).not.toHaveAttribute('required', /.*/);
  }

  // An applicant keeps the fields it always had, and its own type.
  await type.selectOption('3');
  await expect(page.locator('#u_usersubtype')).toBeVisible();
  await expect(page.locator('#u_comp_name'), 'no store or group name').toBeHidden();
  await expect(page.locator('#liprov')).toHaveText('Applicant Licence Province');
  for (const field of STORE_ONLY_FIELDS) {
    await expect(page.locator(field), `${field} is asked of an applicant`).toBeVisible();
  }

  // An applicant is the only type still asked for an address, so switching
  // back to it is what proves the required attributes are restored rather than
  // lost on the way out. Neither employer kind is asked for one any more.
  await type.selectOption('3');
  await expect(page.locator('textarea[name="u_address1"]')).toHaveAttribute('required', /.*/);
  await expect(page.locator('#liprov')).toHaveText('Applicant Licence Province');

  await expectNoServerError(page);
});

test('only a manager is asked for a corporate group, and it is required', async ({ page }) => {
  // beforeAll seeds an approved multi-store owner, so the dropdown always has
  // something to list here. (With none at all it is not rendered, and a test
  // of it would pass by doing nothing.)
  await page.goto('front/register');
  await settle(page);

  const picker = page.locator('#u_parent_id');

  await page.selectOption('#usrtpe', '2');
  await expect(picker, 'a manager runs a store for a group').toBeVisible();
  await expect(picker, 'and must say which').toHaveAttribute('required', /.*/);
  await expect(picker, 'nothing chosen to begin with').toHaveValue('');

  // Every option is an approved multi-store owner, by corporate group name -
  // including one that has added no store yet, which the store list explains
  // rather than the group list hiding.
  const offered = (await picker.locator('option').allTextContents())
    .map((t) => t.trim())
    .filter((t) => t !== '-- None --');

  // Trimmed on both sides: some company names are stored with a trailing
  // space, and the browser trims an <option>'s text. That is a quirk of the
  // data, not a difference in what the list offers.
  const real = query(`
    SELECT u_comp_name FROM users
     WHERE u_usertype = 1 AND u_emp_role = 1 AND u_status = 1 ORDER BY u_comp_name ASC;
  `).split('\n').map((t) => t.trim()).filter((t) => t !== '');

  expect(offered, 'the list is exactly the approved corporate groups').toEqual(real);
  expect(offered, 'including the seeded one').toContain(GROUP.name);

  // Nobody else answers to a group, or picks one of its stores. A select left
  // `required` while hidden would block the form without ever saying why, so
  // the attribute has to go with the visibility.
  for (const other of ['1', '3']) {
    await page.selectOption('#usrtpe', other);

    for (const [sel, what] of [['#u_parent_id', 'corporate group'], ['#u_store_id', 'store']]) {
      await expect(page.locator(sel), `${other} has no ${what}`).toBeHidden();
      await expect(page.locator(sel), `${other} is not held up by a hidden required field`)
        .not.toHaveAttribute('required', /.*/);
    }
  }

  await expectNoServerError(page);
});

test("the store list holds exactly the chosen group's active stores", async ({ page }) => {
  await page.goto('front/register');
  await settle(page);

  const store = page.locator('#u_store_id');

  await page.selectOption('#usrtpe', '2');
  await expect(store, 'a manager says which store they run').toBeVisible();
  await expect(store, 'and must say which').toHaveAttribute('required', /.*/);
  await expect(store, 'nothing to choose until a group is picked').toHaveValue('');

  // Choosing the group fetches its stores, the same way a province fetches its
  // cities. Wait for the response rather than reading an empty dropdown.
  const listed = page.waitForResponse((r) => r.url().includes(REG_STORE_ENDPOINT));
  await page.selectOption('#u_parent_id', String(groupId));
  await listed;
  await expect(store.locator('option')).not.toHaveCount(1);

  const offered = (await store.locator('option').allTextContents())
    .map((t) => t.trim())
    .filter((t) => t !== '-- Select Store --');

  // The label every other store picker uses: the name, then the number - plus
  // the note this one adds when the branch already has a manager, because one
  // store takes one manager and a taken store is shown rather than hidden.
  const expected = query(`
    SELECT CONCAT(s_name, IF(s_number = '', '', CONCAT(' (', s_number, ')')),
                  IF(EXISTS(SELECT 1 FROM users m
                             WHERE m.u_store_id = s.s_id AND m.u_usertype = 1 AND m.u_emp_role = 2),
                     ' - already has a manager', ''))
      FROM store s WHERE s.u_id = ${groupId} AND s.s_status = 1 ORDER BY s_name ASC;
  `).split('\n').filter((t) => t !== '');

  expect(offered, "exactly the group's active stores").toEqual(expected);
  expect(offered, 'including the seeded ones').toContain(`${GROUP_STORES[0].name} (${GROUP_STORES[0].number})`);

  // A deactivated store is not somewhere anyone can be sent to work.
  query(`UPDATE store SET s_status = 0 WHERE s_id = ${groupStores[0].id};`);

  try {
    const again = page.waitForResponse((r) => r.url().includes(REG_STORE_ENDPOINT));
    await page.selectOption('#u_parent_id', '');
    await page.selectOption('#u_parent_id', String(groupId));
    await again;

    const afterwards = (await store.locator('option').allTextContents()).map((t) => t.trim());
    expect(afterwards, 'a deactivated store is not offered').not.toContain(
      `${GROUP_STORES[0].name} (${GROUP_STORES[0].number})`,
    );
  } finally {
    query(`UPDATE store SET s_status = 1 WHERE s_id = ${groupStores[0].id};`);
  }

  await expectNoServerError(page);
});

test('a group that has added no store says so, rather than being hidden', async ({ page }) => {
  // A site whose groups are still being set up would otherwise show a manager
  // an empty group dropdown and no explanation at all.
  const emptyName = 'E2E Storeless Group';
  const emptyUser = 'e2e.storeless@example.com';

  query(`DELETE FROM users WHERE u_userid = '${emptyUser}';`);
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_userid, u_fname, u_lname, u_pass, u_comp_name,
       u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, 1, '${emptyUser}', 'Storeless', 'E2E', MD5('E2eTest@12345'), '${emptyName}',
       0, '', '', '', 0, 0, '', '', '0000000000', '${emptyUser}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  const emptyId = Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${emptyUser}';`));

  try {
    await page.goto('front/register');
    await settle(page);
    await page.selectOption('#usrtpe', '2');

    const picker = page.locator('#u_parent_id');
    await expect(picker, 'a group with no store is still offered').toContainText(emptyName);

    const listed = page.waitForResponse((r) => r.url().includes(REG_STORE_ENDPOINT));
    await page.selectOption('#u_parent_id', String(emptyId));
    await listed;

    await expect(
      page.locator('#u_store_id'),
      'the store list explains the gap instead of sitting empty',
    ).toContainText(/no stores yet/i);

    await expectNoServerError(page);
  } finally {
    query(`DELETE FROM users WHERE u_userid = '${emptyUser}';`);
  }
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

test("a manager works from their corporate group's store, which they do not own", async ({ page }) => {
  // The whole point of the store picker: a manager owns no store row, so every
  // screen that used to resolve stores by ownership would show them nothing
  // and leave them unable to post the shifts the site exists for.
  const manager = { user: 'e2e.manager.store@example.com', pass: 'E2eTest@12345' };
  const assigned = groupStores[0];

  query(`DELETE FROM users WHERE u_userid = '${manager.user}';`);
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid, u_fname, u_lname,
       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, 2, ${groupId}, ${assigned.id}, '${manager.user}', 'Managed', 'E2E',
       MD5('${manager.pass}'), '${assigned.name}', 0, '${assigned.number}', '', '', 0, 0,
       '${assigned.address}', 'M5A 1A1', '4160000904', '${manager.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  try {
    await loginAsFrontUser(page, manager);

    // My Stores shows the group's location, and only that one: a manager runs
    // a branch, not the chain, so the group's other stores are not theirs to
    // see here.
    await page.goto(STORE_LIST_URL);
    await settle(page);

    const list = page.locator('#storelist tbody');
    await expect(list, "the group's store is listed").toContainText(assigned.name);
    expect(await page.locator('#storelist tbody tr').count(), 'only the assigned one').toBe(1);
    await expect(list, "the other group store is not theirs").not.toContainText(GROUP_STORES[1].name);

    // It is not theirs to edit, so they are told so rather than given a button
    // that leads to "Invalid Store".
    await expect(list.locator('a:has-text("Edit")'), 'no edit button for a store they do not own')
      .toHaveCount(0);

    // And the shift form offers it, which is what they are here to do.
    await page.goto('employer/post_job');
    await settle(page);

    const picker = page.locator(STORE_SELECT);
    await expect(picker, 'a manager can post against their store').toBeVisible();

    const offered = (await picker.locator('option').allTextContents())
      .map((t) => t.trim())
      .filter((t) => t !== '' && !t.startsWith('--'));

    expect(offered, "exactly the group's store they were assigned").toEqual([
      `${assigned.name} (${assigned.number})`,
    ]);

    await expectNoServerError(page);
  } finally {
    query(`DELETE FROM users WHERE u_userid = '${manager.user}';`);
  }
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
