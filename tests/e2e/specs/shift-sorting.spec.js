// @ts-check
/**
 * Every list of shifts is ordered by the shift date, soonest first - except the
 * admin's own list, which reads by record number, newest first (the last test
 * here).
 *
 * The seeded shifts (helpers/front.js) are inserted in an order that is neither
 * chronological nor the order their `dd-mm-yyyy` text sorts in, so a list that
 * falls back to the record number or to a string comparison fails here.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const {
  EXPECTED,
  seedShiftFixture,
  removeShiftFixture,
  loginAsAgency,
  loginAsApplicant,
} = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

test.beforeAll(() => {
  seedShiftFixture();
});

test.afterAll(() => {
  removeShiftFixture();
});

/**
 * The seeded shift titles, in the order they appear down the page.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
async function seededOrder(page, selector) {
  const text = await page.locator(selector).allTextContents();

  return text
    .map((t) => (t.match(/E2E-SHIFT-[ABC]/) || [])[0])
    .filter((t) => t !== undefined);
}

/**
 * Shift date by title, for the lists that print no date of their own.
 *
 * @returns {Map<string, string>}
 */
function datesByTitle() {
  return new Map(
    query(`
      SELECT p_job_title, COALESCE(p_date_start, '9999-12-31') FROM post_job
       WHERE p_status = 1 AND p_approved = 1;
    `)
      .split('\n')
      .filter((line) => line !== '')
      .map((line) => line.split('\t'))
      .map(([title, date]) => [title, date]),
  );
}

test('public home page lists shifts soonest first', async ({ page }) => {
  // The home page renders `.wz-job` cards in the order the server produced;
  // the tabs and "Load More" only ever hide cards, so every card is in the
  // DOM to be read even before "Load More" is pressed.
  await page.goto('');
  await settle(page);

  expect(await seededOrder(page, '#wz-jobs .wz-job .wz-job-title')).toEqual(EXPECTED);

  // And the whole list, not just the seeded rows: walking down the page, the
  // shift dates never go backwards, and every unreadable date is at the end.
  // (Cards themselves carry no ISO date, so look each title's date up once.)
  const titles = (await page.locator('#wz-jobs .wz-job .wz-job-title').allTextContents()).map((t) =>
    t.trim(),
  );

  const dateOf = datesByTitle();

  const dates = titles.map((t) => dateOf.get(t) ?? '');

  expect(dates.length).toBeGreaterThan(1);
  expect(dates).not.toContain(''); // every card matched a database row
  expect(dates, 'shift dates ascend down the page').toEqual([...dates].sort());
  await expectNoServerError(page);
});

test('related shifts on a shift detail page are soonest first', async ({ page }) => {
  const pid = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SHIFT-A';");

  await page.goto(`front/job_detail/${pid}`);
  await settle(page);

  // The sidebar is capped and leaves the shift being read out, so the seeded
  // three are not all in it - what has to hold is that whatever it does list
  // reads soonest first, and never the shift the page is already showing.
  const titles = (await page.locator('.wz-related .wz-related-title').allTextContents()).map((t) =>
    t.trim(),
  );

  expect(titles.length).toBeGreaterThan(1);
  expect(titles).not.toContain('E2E-SHIFT-A');

  const dateOf = datesByTitle();
  const dates = titles.map((t) => dateOf.get(t) ?? '');

  expect(dates).not.toContain(''); // every row matched a database row
  expect(dates, 'shift dates ascend down the sidebar').toEqual([...dates].sort());
  await expectNoServerError(page);
});

test('agency dashboard lists recent shifts soonest first, not by record number', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/dashboard');
  await settle(page);

  expect(await seededOrder(page, '.dashboard-gravity-list li h4')).toEqual(EXPECTED);
  await expectNoServerError(page);
});

test('agency shift list is soonest first and sorts its date column chronologically', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/all_jobs');
  await settle(page);

  // The list is a DataTable, which hides the internal Job id column and takes
  // its cells out of the DOM - so Shift ID is the first cell of a row and the
  // date is the fourth, the store and the merged city/province column sitting
  // between them. The back-office specs read their shifted columns the same way.
  expect(await seededOrder(page, '#joblist tbody tr td:nth-child(1)')).toEqual(EXPECTED);

  const dates = await page
    .locator('#joblist tbody tr td:nth-child(4)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(dates.length).toBeGreaterThan(1);
  expect(dates, 'shift dates ascend down the page').toEqual([...dates].sort());
  await expectNoServerError(page);
});

test('pharmacist applied shifts are soonest first, not by application number', async ({ page }) => {
  await loginAsApplicant(page);
  await page.goto('applicant/applied_jobs');
  await settle(page);

  expect(await seededOrder(page, '#joblist tbody tr td:nth-child(2)')).toEqual(EXPECTED);

  const dates = await page
    .locator('#joblist tbody tr td:nth-child(4)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(dates.length).toBeGreaterThan(1);
  expect(dates, 'shift dates ascend down the page').toEqual([...dates].sort());
  await expectNoServerError(page);
});

// The admin list is the one exception to the rule this file otherwise checks:
// it is not ordered by shift date at all, but by record number, newest first.
// Its date column still sorts chronologically once clicked, with an unreadable
// date at the end.
test('admin shift list is newest first and sorts its date column chronologically', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/postjobs');
  await settle(page);

  // The seeded shifts are inserted C, A, B, so by record number they read
  // backwards from that - an order no shift-date sort produces.
  expect(await seededOrder(page, '#example1 tbody tr td:nth-child(1)')).toEqual([
    'E2E-SHIFT-B',
    'E2E-SHIFT-A',
    'E2E-SHIFT-C',
  ]);

  // Sorting by the date column: ascending on the first click, and every
  // unreadable date at the far end of it. The table scrolls sideways, so the
  // header that takes the click is the floating copy above it - the one inside
  // the scrolling body is hidden.
  await page.locator('#example1_wrapper th:visible', { hasText: 'Shift Date' }).first().click();
  await settle(page);

  const dates = await page
    .locator('#example1 tbody tr td:nth-child(6)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(dates.length).toBeGreaterThan(1);
  expect(dates, 'shift dates ascend down the page').toEqual([...dates].sort());

  // Last page of the list - where a shift with no usable date belongs.
  const undated = Number(scalar('SELECT COUNT(*) FROM post_job WHERE p_date_start IS NULL;'));
  test.skip(undated === 0, 'every shift has a readable date');

  const last = page.locator('#example1_paginate .paginate_button:not(.next):not(.previous)').last();
  await last.click();
  await settle(page);

  const order = await page
    .locator('#example1 tbody tr td:nth-child(6)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(order.at(-1), 'a shift with no readable date sorts last, not first').toBe('9999-12-31');
});

test('the applicant list keeps its name column and does not borrow the shift sort', async ({ page }) => {
  await loginAsAgency(page);

  const pid = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SHIFT-A';");
  await page.goto(`employer/applied_applicants/${pid}`);
  await settle(page);

  // It used to share the shift list's table id, which hid column 0 - the
  // applicant's name - and ordered the table by Status.
  const header = page.locator('#candidatelist thead th').first();
  await expect(header, 'the applicant name column is visible').toBeVisible();
  await expect(header).toHaveText('Applicant');

  await expectNoServerError(page);
});
