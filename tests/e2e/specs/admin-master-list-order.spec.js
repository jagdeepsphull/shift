// @ts-check
/**
 * The order of the other seven lists the agency keeps, and the arrows that set
 * it - Province, City, Softwares, Services, Additional Details, Resources Menu
 * and Testimonials.
 *
 * Shift For got the arrows first and has a file of its own,
 * `admin-shift-for-order.spec.js`, which follows one list all the way out to
 * every dropdown that offers it. This one covers the seven that came after: the
 * arrows on each back-office list, and that what they set is what the front of
 * the site reads.
 *
 * Two of the seven are ordered inside a group rather than end to end - a city
 * among the cities of its province, a resources link among the children of its
 * menu - so the arrows stop at the edge of a group rather than at the edge of
 * the table. That is what `groupColumn` below is for, and it is the part most
 * worth testing: the top row of the second province has a row above it on
 * screen and still must not offer an up arrow.
 *
 * The rows are the site's own. These are lookup tables other specs depend on
 * the contents of, so nothing is seeded, and every test puts the order back the
 * way it found it.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query } = require('../helpers/db');

/**
 * @typedef {object} List
 * @property {string} module      the segment under /sadmin
 * @property {string} table       the table behind it
 * @property {string} key         its primary key column
 * @property {string} orderColumn the position column the arrows write
 * @property {string} nameColumn  what the list shows, and the tie-break
 * @property {?string} groupColumn the column it is ordered within, if any
 * @property {string} tableId     the DataTable's id - Resources has its own
 * @property {number} nameCell    1-based cell holding the name on screen
 */

/** @type {List[]} */
const LISTS = [
  { module: 'province', table: 'province', key: 'p_id', orderColumn: 'p_order', nameColumn: 'p_name', groupColumn: null, tableId: 'example1', nameCell: 1 },
  { module: 'city', table: 'city', key: 'c_id', orderColumn: 'c_order', nameColumn: 'c_name', groupColumn: 'c_province', tableId: 'example1', nameCell: 1 },
  { module: 'softwareskills', table: 'software_skills', key: 'ss_id', orderColumn: 'ss_order', nameColumn: 'ss_name', groupColumn: null, tableId: 'example1', nameCell: 1 },
  { module: 'storeservice', table: 'store_service', key: 'st_id', orderColumn: 'st_order', nameColumn: 'st_service_name', groupColumn: null, tableId: 'example1', nameCell: 1 },
  { module: 'testimonials', table: 'testimonial', key: 't_id', orderColumn: 't_order', nameColumn: 't_title', groupColumn: null, tableId: 'example1', nameCell: 1 },
  { module: 'resources', table: 'headermenu', key: 'm_id', orderColumn: 'm_order', nameColumn: 'm_name', groupColumn: 'm_parentid', tableId: 'res-table-ex', nameCell: 1 },
];

/**
 * Additional Details is left off the list above. It is one row on this site, and
 * a list of one has no order to get wrong - every assertion here would pass on
 * an empty implementation. It shares the arrows and the mover with the six, so
 * what is left untested is the data rather than the code.
 */

/**
 * Rows as objects, from the client's tab-separated output.
 *
 * @param {string} sql
 * @param {string[]} columns the names to give the columns, in order
 */
function rows(sql, columns) {
  const out = query(sql);

  if (out === '') {
    return [];
  }

  // Trimmed: a few of these names were typed with a trailing space, and the
  // browser drops it when it renders the cell. Comparing the untrimmed value
  // against the screen would fail on the typing rather than on the order.
  return out.split('\n').map((line) => {
    const values = line.split('\t');

    return Object.fromEntries(columns.map((name, i) => [name, (values[i] ?? '').trim()]));
  });
}

/** One list as the database holds it, in the order the back office draws it. */
function savedOrder(list) {
  const group = list.groupColumn === null ? '' : `${list.groupColumn} ASC, `;
  const columns = ['id', 'name', 'position'].concat(list.groupColumn === null ? [] : ['group']);
  const select = `${list.key}, ${list.nameColumn}, ${list.orderColumn}`
    + (list.groupColumn === null ? '' : `, ${list.groupColumn}`);

  return rows(
    `SELECT ${select} FROM ${list.table} ORDER BY ${group}${list.orderColumn} ASC, ${list.nameColumn} ASC;`,
    columns,
  );
}

/** The names on screen, top to bottom. Column 0 is the id, hidden by DataTables. */
async function shown(page, list) {
  // DataTables hides the id column by taking the cell out of the row, and it
  // does that during its own setup - after the load event, and after the page
  // has gone quiet. Read before that and the first cell is still the id, which
  // is what made this flaky on the longer lists.
  await page.waitForFunction((id) => {
    const jq = window.jQuery;

    return !!jq && !!jq.fn.dataTable && jq.fn.dataTable.isDataTable('#' + id);
  }, list.tableId);

  const cells = await page
    .locator(`#${list.tableId} tbody tr td:nth-child(${list.nameCell})`)
    .allInnerTexts();

  return cells.map((t) => t.trim());
}

/**
 * Put a list back exactly as a test found it.
 *
 * @param {List} list
 * @param {ReturnType<typeof savedOrder>} original
 */
function restore(list, original) {
  for (const row of original) {
    query(`UPDATE ${list.table} SET ${list.orderColumn} = ${Number(row.position)} WHERE ${list.key} = ${Number(row.id)};`);
  }
}

for (const list of LISTS) {
  // A list of nought, one or two rows has no order to get wrong, and every
  // assertion below would pass on an empty implementation. Testimonials is one
  // of these on a fresh database - what goes untested there is the data rather
  // than the code, which the longer lists share.
  const describeList = savedOrder(list).length < 3 ? test.describe.skip : test.describe;

  describeList(`the ${list.module} list`, () => {
    /** @type {ReturnType<typeof savedOrder>} */
    let original;

    test.beforeAll(() => {
      original = savedOrder(list);
    });

    test.afterAll(() => {
      restore(list, original);
    });

    test.beforeEach(async ({ page }) => {
      await loginAsAdmin(page);
      await page.goto(`sadmin/${list.module}`);
      await settle(page);
    });

    test('arrives in the saved order, not one the browser chose', async ({ page }) => {
      // Only the first page of the table: these lists are paged at twenty, and
      // City is over a thousand rows.
      const saved = savedOrder(list).map((r) => r.name).slice(0, 20);

      expect(await shown(page, list)).toEqual(saved);

      // Sorting is off: a heading that re-sorted the table would show one order
      // while the arrows moved rows about in another.
      await expect(page.locator(`#${list.tableId} thead th.sorting`)).toHaveCount(0);
      await expect(page.locator(`#${list.tableId} thead th.sorting_asc`)).toHaveCount(0);

      await expectNoServerError(page);
    });

    test('the top of the list offers no arrow off the end of it', async ({ page }) => {
      const first = page.locator(`#${list.tableId} tbody tr`).first();

      await expect(first.locator('a[href*="/moveup/"]'), 'the top row cannot go up').toHaveCount(0);
      await expect(first.locator('a[href*="/movedown/"]')).toHaveCount(1);

      await expectNoServerError(page);
    });

    test('the down arrow moves a row one place, and the up arrow puts it back', async ({ page }) => {
      const before = await shown(page, list);

      test.skip(before.length < 3, 'a list of one or two has nothing to move');

      const [firstName, secondName] = before;

      await page.locator(`#${list.tableId} tbody tr`).first().locator('a[href*="/movedown/"]').click();
      await settle(page);

      const afterDown = await shown(page, list);

      expect(afterDown[0], 'the row below has come up').toBe(secondName);
      expect(afterDown[1], 'the row moved has gone down').toBe(firstName);

      // The rest of the list is where it was - a move is a swap of two rows,
      // not a reshuffle of everything under them.
      expect(afterDown.slice(2)).toEqual(before.slice(2));

      // And it is the database that says so, not just the page.
      expect(savedOrder(list).map((r) => r.name).slice(0, afterDown.length)).toEqual(afterDown);

      await page.locator(`#${list.tableId} tbody tr`).nth(1).locator('a[href*="/moveup/"]').click();
      await settle(page);

      expect(await shown(page, list)).toEqual(before);

      await expectNoServerError(page);
    });

    test('a move off the end is refused rather than silently reshuffling', async ({ page }) => {
      const before = savedOrder(list).map((r) => r.name);
      const topId = savedOrder(list)[0].id;

      // No arrow draws this, so it is a typed URL or a second tab acting on a
      // page from before somebody else moved things.
      await page.goto(`sadmin/${list.module}/moveup/${topId}`);
      await settle(page);

      await expect(page.locator('.alert-warning')).toContainText('already at the top');
      expect(savedOrder(list).map((r) => r.name), 'nothing moved').toEqual(before);

      await expectNoServerError(page);
    });
  });
}

test.describe('a list ordered within a group', () => {
  // City. Its rows are numbered per province, so the arrows have to stop at
  // each province rather than at the top and bottom of the table.
  const city = LISTS.find((l) => l.module === 'city');

  /** @type {ReturnType<typeof savedOrder>} */
  let original;

  test.beforeAll(() => {
    original = savedOrder(city);
  });

  test.afterAll(() => {
    restore(city, original);
  });

  test('every province starts its numbering again at one', () => {
    const saved = savedOrder(city);
    const counters = {};

    for (const row of saved) {
      counters[row.group] = (counters[row.group] ?? 0) + 1;

      expect(
        Number(row.position),
        `${row.name} is number ${row.position} of province ${row.group}`,
      ).toBe(counters[row.group]);
    }
  });

  test('the first city of a province has no up arrow, wherever it sits on screen', async ({ page }) => {
    const saved = savedOrder(city);

    // The row that starts the second province. It has rows above it in the
    // table, so an implementation that only disabled the very first row of the
    // table would draw it an up arrow that moves it into another province.
    const firstGroup = saved[0].group;
    const boundary = saved.find((r) => r.group !== firstGroup);

    expect(boundary, 'more than one province with cities').toBeTruthy();

    await loginAsAdmin(page);
    await page.goto(`sadmin/city/moveup/${Number(boundary.id)}`);
    await settle(page);

    await expect(page.locator('.alert-warning')).toContainText('already at the top');

    // Nothing crossed the boundary.
    expect(savedOrder(city).map((r) => `${r.group}:${r.name}`))
      .toEqual(saved.map((r) => `${r.group}:${r.name}`));

    await expectNoServerError(page);
  });

  test('the order reaches the city dropdown the public picks from', async ({ page }) => {
    // A province with a handful of active cities. One arrow is enough to prove
    // the point and each is a round trip through a list of over a thousand
    // rows, so this moves one city rather than walking one up the whole
    // province.
    const province = rows(
      `SELECT c.c_province, COUNT(*) n FROM city c
       JOIN province p ON p.p_id = c.c_province AND p.p_status = 1
       WHERE c.c_status = 1
       GROUP BY c.c_province HAVING n >= 3 ORDER BY n ASC LIMIT 1;`,
      ['province', 'n'],
    )[0];

    expect(province, 'an active province with cities to reorder').toBeTruthy();

    const active = () => rows(
      `SELECT c_id, c_name FROM city
       WHERE c_province = ${Number(province.province)} AND c_status = 1
       ORDER BY c_order ASC, c_name ASC;`,
      ['id', 'name'],
    ).map((r) => r.name);

    const before = active();

    await loginAsAdmin(page);

    // The first active city of the province, sent down one place. Its
    // neighbours are alphabetical, so afterwards the saved order is one no
    // screen reading these by name could produce.
    const first = rows(
      `SELECT c_id FROM city WHERE c_province = ${Number(province.province)} AND c_status = 1
       ORDER BY c_order ASC, c_name ASC LIMIT 1;`,
      ['id'],
    )[0];

    await page.goto(`sadmin/city/movedown/${Number(first.id)}`);
    await settle(page);

    const wanted = active();

    expect(wanted, 'the move changed the order').not.toEqual(before);
    expect(wanted[0], 'the city below has come up').toBe(before[1]);
    expect(wanted[1], 'the city moved has gone down').toBe(before[0]);

    // The public sign-up form, which fills its city list over ajax once a
    // province is picked.
    await page.context().clearCookies();
    await page.goto('front/signup');
    await settle(page);

    // The city list is fetched for the chosen province, so wait for it to
    // arrive rather than reading an empty dropdown. `force` because Select2 has
    // hidden the real select behind its own control by now.
    const cities = page.waitForResponse((r) => r.url().includes('ajax_getcitylist'));

    await page.selectOption('#provincelist', String(province.province), { force: true });
    await cities;
    await settle(page);

    const offered = (await page
      .locator('#city option:not([value=""])')
      .allTextContents()).map((t) => t.trim());

    expect(offered, 'the dropdown follows the saved order').toEqual(wanted);

    await expectNoServerError(page);
  });
});

test('the Resources menu on the front follows the order the arrows set', async ({ page }) => {
  const list = LISTS.find((l) => l.module === 'resources');
  const original = savedOrder(list);

  try {
    // A menu with a few active children, and its last child moved to the top.
    // A menu the Resources page actually draws as a menu: the top-level rows
    // that carry a link of their own are shown as quick-link tiles instead, and
    // only the linkless ones get a heading with their children under it.
    const parent = rows(
      `SELECT c.m_parentid, COUNT(*) n FROM headermenu c
       JOIN headermenu p ON p.m_id = c.m_parentid
         AND p.m_status = 1 AND p.m_parentid = 0 AND (p.m_link IS NULL OR p.m_link = '')
       WHERE c.m_status = 1
       GROUP BY c.m_parentid HAVING n >= 3 ORDER BY n ASC LIMIT 1;`,
      ['parent', 'n'],
    )[0];

    expect(parent, 'a menu the Resources page draws with its children').toBeTruthy();

    const active = () => rows(
      `SELECT m_id, m_name FROM headermenu WHERE m_parentid = ${Number(parent.parent)} AND m_status = 1
       ORDER BY m_order ASC, m_name ASC;`,
      ['id', 'name'],
    );

    const before = active();

    await loginAsAdmin(page);

    // The menu's first link, sent down one place. Its neighbours are
    // alphabetical, so afterwards the saved order is one the old view - which
    // read these by name - could not produce.
    await page.goto(`sadmin/resources/movedown/${Number(before[0].id)}`);
    await settle(page);

    const wanted = active().map((r) => r.name);

    expect(wanted[0], 'the link below has come up').toBe(before[1].name);
    expect(wanted[1], 'the link moved has gone down').toBe(before[0].name);

    const parentName = rows(
      `SELECT m_name FROM headermenu WHERE m_id = ${Number(parent.parent)};`,
      ['name'],
    )[0].name;

    // Signed out: the Resources page is the one screen here the public sees.
    await page.context().clearCookies();
    await page.goto('front/resources');
    await settle(page);

    // The page draws each linkless top-level row as an accordion card, its
    // children the links inside. The panel starts collapsed, so the text is
    // read out of the markup rather than waited for on screen.
    const card = page.locator('.wz-res-card')
      .filter({ has: page.locator('.wz-res-name', { hasText: parentName }) })
      .first();

    await expect(card, `the ${parentName} menu is on the page`).toHaveCount(1);

    const offered = (await card.locator('.wz-res-links a.wz-res-link > span:first-child')
      .allTextContents()).map((t) => t.trim());

    expect(offered.length, 'the menu has its links on the page').toBeGreaterThan(1);
    expect(offered, 'the menu follows the saved order').toEqual(wanted);

    await expectNoServerError(page);
  } finally {
    restore(list, original);
  }
});
