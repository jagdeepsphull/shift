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
const { query } = require('../helpers/db');

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
