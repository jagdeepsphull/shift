// @ts-check
/**
 * The order of the Shift For list, and the arrows that set it.
 *
 * Every screen offering these read them alphabetically, which puts "Dental
 * Assistant" above "Pharmacist (R Ph)" on a site where nearly every shift is
 * for a pharmacist. Alphabetical is an accident of spelling; `sf_order` is the
 * order somebody chose, and what is worth testing is that the choice survives
 * the trip - into the database, back onto the list, and out to the dropdowns
 * that offer these to employers and applicants.
 *
 * The rows are the site's own - `shift_for` is a lookup table a handful of rows
 * long, and other specs depend on the names in it - so nothing is seeded here.
 * Each test puts the order back the way it found it.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { AGENCY, seedShiftFixture, removeShiftFixture, loginAsAgency } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

/**
 * Rows as objects. `query()` hands back the client's tab-separated output, and
 * these tests want more than the one column `scalar()` gives.
 *
 * @param {string} sql
 * @param {string[]} columns the names to give the columns, in order
 */
function rows(sql, columns) {
  const out = query(sql);

  if (out === '') {
    return [];
  }

  return out.split('\n').map((line) => {
    const values = line.split('\t');

    return Object.fromEntries(columns.map((name, i) => [name, values[i]]));
  });
}

/** The list as the database holds it, in the order every screen reads it. */
function savedOrder() {
  return rows(
    'SELECT sf_id, sf_name, sf_order FROM shift_for ORDER BY sf_order ASC, sf_name ASC;',
    ['sf_id', 'sf_name', 'sf_order'],
  );
}

/** Put the list back exactly as it was, whatever the test did to it. */
let original;

test.beforeAll(() => {
  original = savedOrder();
});

test.afterAll(() => {
  for (const row of original) {
    query(`UPDATE shift_for SET sf_order = ${Number(row.sf_order)} WHERE sf_id = ${Number(row.sf_id)};`);
  }
});

/** The names on the screen, top to bottom. */
async function shown(page) {
  const cells = await page.locator('#example1 tbody tr td:nth-child(1)').allInnerTexts();

  return cells.map((t) => t.trim());
}

test.describe('the back office list', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('sadmin/shift_for');
    await settle(page);
  });

  test('the list arrives in the saved order, not one the browser chose', async ({ page }) => {
    // The premise. Column 0 is the id, hidden by DataTables, so the first cell
    // on screen is the name.
    const saved = savedOrder().map((r) => r.sf_name);

    expect(await shown(page)).toEqual(saved);

    // Sorting is off for this list: a heading that re-sorted it would show one
    // order while the arrows moved rows about in another.
    await expect(page.locator('#example1 thead th.sorting')).toHaveCount(0);
    await expect(page.locator('#example1 thead th.sorting_asc')).toHaveCount(0);

    await expectNoServerError(page);
  });

  test('the ends of the list offer no arrow off the end of it', async ({ page }) => {
    const rowsOnScreen = page.locator('#example1 tbody tr');
    const count = await rowsOnScreen.count();

    const first = rowsOnScreen.first();
    const last = rowsOnScreen.nth(count - 1);

    await expect(first.locator('a[href*="/moveup/"]'), 'the top row cannot go up').toHaveCount(0);
    await expect(first.locator('a[href*="/movedown/"]')).toHaveCount(1);

    await expect(last.locator('a[href*="/movedown/"]'), 'the bottom row cannot go down').toHaveCount(0);
    await expect(last.locator('a[href*="/moveup/"]')).toHaveCount(1);

    await expectNoServerError(page);
  });

  test('the down arrow moves a row one place, and the up arrow puts it back', async ({ page }) => {
    const before = await shown(page);
    const [firstName, secondName] = before;

    await page.locator('#example1 tbody tr').first().locator('a[href*="/movedown/"]').click();
    await settle(page);

    const afterDown = await shown(page);

    expect(afterDown[0], 'the row below has come up').toBe(secondName);
    expect(afterDown[1], 'the row moved has gone down').toBe(firstName);

    // The rest of the list is where it was - a move is a swap of two rows, not
    // a reshuffle of everything under them.
    expect(afterDown.slice(2)).toEqual(before.slice(2));

    // And it is the database that says so, not just the page.
    expect(savedOrder().map((r) => r.sf_name)).toEqual(afterDown);

    // Back up again.
    await page.locator('#example1 tbody tr').nth(1).locator('a[href*="/moveup/"]').click();
    await settle(page);

    expect(await shown(page)).toEqual(before);

    await expectNoServerError(page);
  });

  test('every row ends up with a place of its own', async ({ page }) => {
    await page.locator('#example1 tbody tr').first().locator('a[href*="/movedown/"]').click();
    await settle(page);

    // A move renumbers the whole list rather than swapping two numbers, so a
    // table that arrived with ties or gaps comes out of one move with neither.
    const positions = savedOrder().map((r) => Number(r.sf_order));

    expect(positions).toEqual(positions.map((_, i) => i + 1));

    await expectNoServerError(page);
  });

  test('a move off the end is refused rather than silently reshuffling', async ({ page }) => {
    const before = savedOrder().map((r) => r.sf_name);
    const topId = savedOrder()[0].sf_id;

    // No arrow draws this, so it is a typed URL or a second tab acting on a
    // page from before somebody else moved things.
    await page.goto(`sadmin/shift_for/moveup/${topId}`);
    await settle(page);

    await expect(page.locator('.alert-warning')).toContainText('already at the top');
    expect(savedOrder().map((r) => r.sf_name), 'nothing moved').toEqual(before);

    await expectNoServerError(page);
  });
});

/**
 * Move one row to the top of the list, so no screen can be passing by accident.
 *
 * Alphabetical is what every one of these read before; putting the row that
 * sorts last at the front means a screen still reading by name gives a
 * different answer to one reading the saved order.
 *
 * @returns {string} the name now at the top
 */
async function moveBottomRowToTop(page) {
  const saved = savedOrder();

  // The last row that is active. A deactivated one reaches no dropdown at all,
  // so moving that to the top would prove nothing about the screens below.
  const active = savedOrder().filter((r) => activeNames().includes(r.sf_name));
  const bottom = active[active.length - 1];

  for (let i = saved.length - 1; i > 0; i--) {
    await page.goto(`sadmin/shift_for/moveup/${bottom.sf_id}`);
  }

  return bottom.sf_name;
}

/** The active names, in the order every screen is supposed to offer them. */
function activeNames() {
  return rows(
    'SELECT sf_name FROM shift_for WHERE sf_status = 1 ORDER BY sf_order ASC, sf_name ASC;',
    ['sf_name'],
  ).map((r) => r.sf_name);
}

/**
 * The options a select is offering, minus its placeholder.
 *
 * Keyed on the empty value rather than on the wording: the placeholders read
 * "-- Choose Shift Requested For --" on one screen and "Shift Types" on
 * another, and only the value is the same everywhere.
 *
 * @param {string} selector
 */
async function offered(page, selector) {
  const options = await page.locator(`${selector} option:not([value=""])`).allInnerTexts();

  return options.map((t) => t.trim());
}

test('every screen that offers these follows the order, in both areas', async ({ page }) => {
  seedShiftFixture();

  try {
    await loginAsAdmin(page);

    const top = await moveBottomRowToTop(page);
    const wanted = activeNames();

    expect(wanted[0], 'the row moved to the top comes first').toBe(top);

    // A shift and an applicant to open the two edit forms against.
    //
    // The owner's edit form takes a pending shift only - `shiftInScope($id,
    // true)` is p_status 0 and p_approved 0 - and the fixture's are approved,
    // so this one is raised for the purpose. `E2E-SHIFT-` is the prefix the
    // fixture's own teardown sweeps up.
    const agencyId = scalar(`SELECT u_id FROM users WHERE u_userid = '${AGENCY.user}';`);
    const shiftFor = scalar('SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_order ASC LIMIT 1;');

    query(`
      INSERT INTO post_job
        (u_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
         p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
         p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
      VALUES
        (${agencyId}, 'E2E Pharmacy', 'E2E-SHIFT-ORDER-EDIT', 0, 1, 1, ${shiftFor},
         30, 30, '31-12-2026', '2026-12-31', '09:00 - 17:00',
         '', '', 'Seeded by the end-to-end suite.', 0, 0, 0, NOW(), NOW());
    `);

    const shiftId = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SHIFT-ORDER-EDIT';");
    const applicantId = scalar('SELECT u_id FROM users WHERE u_usertype = 2 LIMIT 1;');

    /** Every back-office screen that offers the list, and where it offers it. */
    const adminScreens = [
      ['sadmin/applicant/add', 'select[name="u_usersubtype"]'],
      [`sadmin/applicant/edit/${applicantId}`, 'select[name="u_usersubtype"]'],
      ['sadmin/postjobs/add', 'select[name="p_shift_for"]'],
      [`sadmin/postjobs/edit/${shiftId}`, 'select[name="p_shift_for"]'],
    ];

    for (const [screen, selector] of adminScreens) {
      await page.goto(screen);
      await settle(page);

      expect(await offered(page, selector), `${screen} follows the saved order`).toEqual(wanted);

      await expectNoServerError(page);
    }

    // The public registration form, which nobody has to be signed in to see.
    await page.goto('front/signup');
    await settle(page);

    expect(
      await offered(page, '#register select[name="u_usersubtype"]'),
      'the public sign-up form follows it too',
    ).toEqual(wanted);

    // The owner's two shift forms.
    await loginAsAgency(page);

    for (const [screen, selector] of [
      ['employer/post_job', 'select[name="p_shift_for"]'],
      [`employer/edit_job/${shiftId}`, 'select[name="p_shift_for"]'],
    ]) {
      await page.goto(screen);
      await settle(page);

      expect(await offered(page, selector), `${screen} follows the saved order`).toEqual(wanted);

      await expectNoServerError(page);
    }
  } finally {
    removeShiftFixture();
  }
});

test('the home page shift-type filter follows it as well', async ({ page }) => {
  seedShiftFixture();

  try {
    await loginAsAdmin(page);

    const top = await moveBottomRowToTop(page);

    // The filter offers a type only while a shift on the page is posted for
    // one, so two shifts are raised for two different types - one of them the
    // row just moved to the top. With a single type the assertion below would
    // hold whatever order the view used.
    const agencyId = scalar(`SELECT u_id FROM users WHERE u_userid = '${AGENCY.user}';`);
    const wantedIds = rows(
      'SELECT sf_id, sf_name FROM shift_for WHERE sf_status = 1 ORDER BY sf_order ASC, sf_name ASC LIMIT 2;',
      ['sf_id', 'sf_name'],
    );

    wantedIds.forEach((type, i) => {
      query(`
        INSERT INTO post_job
          (u_id, p_company_name, p_job_title, p_type, p_province, p_city, p_shift_for,
           p_hourly_rate, p_ac_hourly_rate, p_dates, p_date_start, p_shift_time,
           p_skills, p_services, p_jobinfo, p_featured, p_status, p_approved, created, modified)
        VALUES
          (${agencyId}, 'E2E Pharmacy', 'E2E-SHIFT-ORDER-TYPE-${i}', 0, 1, 1, ${Number(type.sf_id)},
           30, 30, '31-12-2026', '2026-12-31', '09:00 - 17:00',
           '', '', 'Seeded by the end-to-end suite.', 0, 1, 1, NOW(), NOW());
      `);
    });

    // Signed out: this is the one screen here the public sees.
    await page.context().clearCookies();
    await page.goto('');
    await settle(page);

    // The filter offers only the types the shifts on the page are actually
    // posted for, so it is a subset - what matters is that the subset is in
    // the saved order and not sorted by name, which is what the view did.
    const shown = await offered(page, '#wz-job-type');
    const wanted = activeNames().filter((name) => shown.includes(name));

    expect(shown.length, 'more than one type on the page to order').toBeGreaterThan(1);
    expect(shown, 'the filter keeps the order the list is read in').toEqual(wanted);
    expect(shown[0], 'and the row moved to the top leads it').toBe(top);

    await expectNoServerError(page);
  } finally {
    removeShiftFixture();
  }
});

test('the order reaches the dropdown an employer picks from', async ({ page }) => {
  seedShiftFixture();

  try {
    // Move whatever is at the bottom to the top, so the answer cannot be the
    // alphabetical order this used to be read in.
    const saved = savedOrder();
    const bottom = saved[saved.length - 1];

    await loginAsAdmin(page);

    for (let i = saved.length - 1; i > 0; i--) {
      await page.goto(`sadmin/shift_for/moveup/${bottom.sf_id}`);
    }

    await settle(page);

    const wanted = savedOrder().filter((r) => Number(r.sf_order) >= 0).map((r) => r.sf_name);

    expect(wanted[0], 'the bottom row is now the top one').toBe(bottom.sf_name);

    await loginAsAgency(page);
    await page.goto('employer/post_job');
    await settle(page);

    // Skipping the "-- Choose --" placeholder. Deactivated rows are not
    // offered, so the screen carries the active ones in the saved order.
    const offered = (await page.locator('select[name="p_shift_for"] option').allInnerTexts())
      .slice(1)
      .map((t) => t.trim());

    const active = rows(
      'SELECT sf_name FROM shift_for WHERE sf_status = 1 ORDER BY sf_order ASC, sf_name ASC;',
      ['sf_name'],
    ).map((r) => r.sf_name);

    expect(offered).toEqual(active);
    expect(offered[0], 'the row moved to the top is offered first').toBe(bottom.sf_name);

    await expectNoServerError(page);
  } finally {
    removeShiftFixture();
  }
});
